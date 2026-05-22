<?php

namespace App\Services\Alerts;

use App\Models\CommercialGoal;
use App\Models\Lead;
use App\Models\Seguimiento;
use App\Models\User;
use App\Notifications\CommercialManagementAlertNotification;
use App\Notifications\CommercialPerformanceAlertNotification;
use App\Support\Modules\LeadPermissions;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CommercialAlertService
{
    public function __construct(
        protected AlertDispatchStore $dispatchStore,
    ) {
    }

    public function process(): array
    {
        if (! config('alerts.commercial.enabled', true)) {
            return [
                'affected' => 0,
                'commercial_mail' => 0,
                'management_mail' => 0,
            ];
        }

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $progressRatio = $this->monthProgressRatio();
        $progressPercent = round($progressRatio * 100, 1);
        $bucket = now()->toDateString();

        $snapshots = $this->resolveCommercialUsers($monthStart)
            ->map(fn (User $commercial): array => $this->buildSnapshot($commercial, $monthStart, $monthEnd, $progressRatio, $progressPercent))
            ->filter(fn (array $snapshot): bool => $snapshot['has_issues'])
            ->values();

        $summary = [
            'affected' => $snapshots->count(),
            'commercial_mail' => 0,
            'management_mail' => 0,
        ];

        if ($snapshots->isEmpty()) {
            return $summary;
        }

        if (config('alerts.commercial.include_commercial_email', true)) {
            foreach ($snapshots as $snapshot) {
                $commercial = $snapshot['commercial'];

                if (! filled($commercial->email)) {
                    continue;
                }

                $dedupeKey = $this->dispatchStore->makeDedupeKey(
                    'commercial.daily.individual',
                    'mail',
                    $commercial->email,
                    $commercial,
                    $bucket,
                );

                if ($this->dispatchStore->wasSent($dedupeKey)) {
                    continue;
                }

                $commercial->notifyNow(new CommercialPerformanceAlertNotification($snapshot));

                $this->dispatchStore->record(
                    'commercial.daily.individual',
                    'mail',
                    $commercial->email,
                    $commercial,
                    $dedupeKey,
                    [
                        'issues' => $snapshot['issues'],
                        'month' => $snapshot['month_label'],
                    ],
                );

                $summary['commercial_mail']++;
            }
        }

        if (config('alerts.commercial.include_management_email', true)) {
            $payload = [
                'affected_count' => $snapshots->count(),
                'month_label' => $monthStart->format('m/Y'),
                'progress_label' => number_format($progressPercent, 1, ',', '.') . '%',
                'rows' => $snapshots->map(fn (array $snapshot): array => [
                    'commercial_name' => $snapshot['commercial_name'],
                    'goal_alert' => $snapshot['goal_alert'],
                    'attainment_label' => $snapshot['attainment_label'],
                    'overdue_followups' => $snapshot['overdue_followups'],
                    'overdue_followups_label' => $snapshot['overdue_followups_label'],
                    'stale_leads' => $snapshot['stale_leads'],
                    'stale_leads_label' => $snapshot['stale_leads_label'],
                ])->all(),
                'url' => $this->buildMetricsUrl($monthStart, $monthEnd),
            ];

            foreach ($this->resolveManagementRecipients() as $manager) {
                $dedupeKey = $this->dispatchStore->makeDedupeKey(
                    'commercial.daily.management',
                    'mail',
                    $manager->email,
                    $manager,
                    $bucket,
                );

                if ($this->dispatchStore->wasSent($dedupeKey)) {
                    continue;
                }

                $manager->notifyNow(new CommercialManagementAlertNotification($payload));

                $this->dispatchStore->record(
                    'commercial.daily.management',
                    'mail',
                    $manager->email,
                    $manager,
                    $dedupeKey,
                    [
                        'affected' => $snapshots->count(),
                        'month' => $monthStart->format('m/Y'),
                    ],
                );

                $summary['management_mail']++;
            }
        }

        return $summary;
    }

    protected function resolveCommercialUsers(Carbon $monthStart): Collection
    {
        $userIds = Lead::query()
            ->whereNotNull('comercial_asignado_id')
            ->pluck('comercial_asignado_id')
            ->merge(
                CommercialGoal::query()
                    ->whereDate('goal_month', $monthStart->toDateString())
                    ->pluck('user_id'),
            )
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn('id', $userIds)
            ->get(['id', 'name', 'email']);
    }

    protected function resolveManagementRecipients(): Collection
    {
        return User::query()
            ->whereNotNull('email')
            ->get(['id', 'name', 'email'])
            ->filter(function (User $user): bool {
                return $user->hasRole('admin')
                    || $user->canAccess(LeadPermissions::ASSIGN)
                    || $user->canAccess(LeadPermissions::VIEW_ALL);
            })
            ->unique('id')
            ->values();
    }

    protected function buildSnapshot(
        User $commercial,
        Carbon $monthStart,
        Carbon $monthEnd,
        float $progressRatio,
        float $progressPercent,
    ): array {
        $goal = CommercialGoal::query()
            ->where('user_id', $commercial->id)
            ->whereDate('goal_month', $monthStart->toDateString())
            ->first();

        $monthlyLeads = Lead::query()
            ->with('ventas:id,lead_id,monto_total')
            ->where('comercial_asignado_id', $commercial->id)
            ->whereBetween('fecha_ingreso', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get([
                'id',
                'comercial_asignado_id',
                'fecha_ingreso',
                'estado_pipeline',
                'resultado_final',
                'fecha_ultimo_seguimiento',
            ]);

        $actualLeads = $monthlyLeads->count();
        $actualSales = $monthlyLeads->filter(fn (Lead $lead): bool => $this->isWon($lead))->count();
        $actualRevenue = (float) $monthlyLeads->sum(fn (Lead $lead): float => (float) $lead->ventas->sum('monto_total'));

        $staleDays = (int) config('alerts.commercial.stale_days', 3);
        $staleThreshold = now()->subDays($staleDays)->toDateString();

        $staleLeads = Lead::query()
            ->where('comercial_asignado_id', $commercial->id)
            ->openPipeline()
            ->where(function ($query) use ($staleThreshold): void {
                $query
                    ->whereDate('fecha_ultimo_seguimiento', '<', $staleThreshold)
                    ->orWhere(function ($leadQuery) use ($staleThreshold): void {
                        $leadQuery
                            ->whereNull('fecha_ultimo_seguimiento')
                            ->whereDate('fecha_ingreso', '<', $staleThreshold);
                    });
            })
            ->count();

        $overdueFollowups = Seguimiento::query()
            ->where('comercial_id', $commercial->id)
            ->where('estado', Seguimiento::STATUS_PENDIENTE)
            ->whereDate('fecha_proxima_accion', '<', today()->toDateString())
            ->count();

        $targetLeads = (int) ($goal?->target_leads ?? 0);
        $targetSales = (int) ($goal?->target_sales ?? 0);
        $targetRevenue = (float) ($goal?->target_revenue ?? 0);
        $hasGoal = $targetLeads > 0 || $targetSales > 0 || $targetRevenue > 0;

        $expectedLeads = $targetLeads * $progressRatio;
        $expectedSales = $targetSales * $progressRatio;
        $expectedRevenue = $targetRevenue * $progressRatio;

        $attainment = match (true) {
            $targetRevenue > 0 => $this->percent($actualRevenue, $targetRevenue),
            $targetSales > 0 => $this->percent($actualSales, $targetSales),
            $targetLeads > 0 => $this->percent($actualLeads, $targetLeads),
            default => null,
        };

        $tolerance = (float) config('alerts.commercial.goal_progress_tolerance', 15);
        $goalAlert = $hasGoal && $attainment !== null && ($progressPercent - $attainment) >= $tolerance;

        $issues = [];

        if ($goalAlert) {
            $issues[] = 'goal_progress';
        }

        if ($overdueFollowups > 0) {
            $issues[] = 'overdue_followups';
        }

        if ($staleLeads > 0) {
            $issues[] = 'stale_leads';
        }

        return [
            'commercial' => $commercial,
            'commercial_name' => $commercial->name,
            'month_label' => $monthStart->format('m/Y'),
            'progress_label' => number_format($progressPercent, 1, ',', '.') . '%',
            'goal_alert' => $goalAlert,
            'has_issues' => $issues !== [],
            'issues' => $issues,
            'attainment_label' => $attainment !== null ? number_format($attainment, 1, ',', '.') . '%' : 'Sin meta',
            'target_leads_label' => $this->formatNumber($targetLeads),
            'actual_leads_label' => $this->formatNumber($actualLeads),
            'expected_leads_label' => number_format($expectedLeads, 1, ',', '.'),
            'target_sales_label' => $this->formatNumber($targetSales),
            'actual_sales_label' => $this->formatNumber($actualSales),
            'expected_sales_label' => number_format($expectedSales, 1, ',', '.'),
            'target_revenue_label' => $this->formatMoney($targetRevenue),
            'actual_revenue_label' => $this->formatMoney($actualRevenue),
            'expected_revenue_label' => $this->formatMoney($expectedRevenue),
            'overdue_followups' => $overdueFollowups,
            'overdue_followups_label' => $this->formatNumber($overdueFollowups),
            'stale_leads' => $staleLeads,
            'stale_leads_label' => $this->formatNumber($staleLeads),
            'stale_days' => $staleDays,
            'url' => $this->buildMetricsUrl($monthStart, $monthEnd, $commercial->id),
        ];
    }

    protected function buildMetricsUrl(Carbon $from, Carbon $until, ?int $commercialId = null): string
    {
        return route('filament.admin.pages.metricas-comerciales', array_filter([
            'period' => 'custom',
            'from' => $from->toDateString(),
            'until' => $until->toDateString(),
            'commercial_id' => $commercialId,
        ], fn ($value): bool => filled($value)));
    }

    protected function monthProgressRatio(): float
    {
        return min(now()->day / now()->daysInMonth, 1);
    }

    protected function isWon(Lead $lead): bool
    {
        return $lead->resultado_final === Lead::RESULTADO_VENDIDO
            || $lead->estado_pipeline === Lead::STAGE_VENTA_CERRADA
            || $lead->ventas->isNotEmpty();
    }

    protected function percent(int|float $value, int|float $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($value / $total) * 100, 1);
    }

    protected function formatNumber(int|float $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    protected function formatMoney(int|float $value): string
    {
        return '$ ' . number_format($value, 0, ',', '.');
    }
}
