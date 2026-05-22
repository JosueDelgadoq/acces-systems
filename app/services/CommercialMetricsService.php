<?php

namespace App\Services;

use App\Models\CommercialGoal;
use App\Models\Lead;
use App\Models\LeadHistory;
use App\Models\Seguimiento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CommercialMetricsService
{
    protected const FUNNEL_STAGES = [
        Lead::STAGE_INGRESADO,
        Lead::STAGE_CONTACTADO,
        Lead::STAGE_ORIENTACION,
        Lead::STAGE_COTIZACION,
        Lead::STAGE_PRESUPUESTO,
        Lead::STAGE_VENTA_CERRADA,
    ];

    protected const CONTACT_HISTORY_EVENTS = [
        'follow_up_logged',
        'follow_up_updated',
        'pipeline_updated',
    ];

    public function build(User $viewer, Carbon $from, Carbon $until, ?int $commercialId = null): array
    {
        [$from, $until] = $this->normalizeRange($from, $until);

        $leads = $this->decorateLeadMetrics(
            $this->loadCohortLeads($viewer, $from, $until, $commercialId),
        );

        $summary = $this->buildSummary($leads);
        $teamPerformance = $this->buildTeamPerformance($leads);
        $sourceBreakdown = $this->buildSourceBreakdown($leads);
        $lossReasons = $this->buildLossReasons($leads);
        $funnel = $this->buildFunnel($leads);
        $alerts = $this->buildOperationalAlerts($viewer, $commercialId);

        $goalMonthStart = $until->copy()->startOfMonth()->startOfDay();
        $goalMonthEnd = $until->copy()->endOfMonth()->endOfDay();

        $goalScoreboard = $this->buildGoalScoreboard(
            viewer: $viewer,
            monthStart: $goalMonthStart,
            monthEnd: $goalMonthEnd,
            commercialId: $commercialId,
            monthlyLeads: $from->equalTo($goalMonthStart) && $until->equalTo($goalMonthEnd)
                ? $leads
                : null,
        );

        $insights = $this->buildExecutiveInsights($summary, $teamPerformance, $sourceBreakdown, $lossReasons, $alerts);

        return [
            'hero' => [
                'eyebrow' => 'Inteligencia comercial',
                'title' => 'Metricas, conversion y lectura de cartera para decidir con criterio.',
                'subtitle' => 'Tablero ejecutivo para seguir desempeno, embudo, respuesta comercial y causas de perdida sin depender de planillas paralelas.',
                'period_label' => $from->format('d/m/Y') . ' al ' . $until->format('d/m/Y'),
                'scope_label' => $this->resolveScopeLabel($commercialId),
                'chips' => [
                    [
                        'label' => 'Leads evaluados',
                        'value' => $this->formatNumber($summary['total']),
                    ],
                    [
                        'label' => 'Cobertura de contacto',
                        'value' => $this->formatPercent($summary['contact_rate_raw']),
                    ],
                    [
                        'label' => 'Conversion final',
                        'value' => $this->formatPercent($summary['close_rate_raw']),
                    ],
                    [
                        'label' => 'Ingresos cerrados',
                        'value' => $this->formatMoney($summary['revenue_raw']),
                    ],
                ],
            ],
            'overview_cards' => $this->buildOverviewCards($summary),
            'funnel' => $funnel,
            'team_performance' => $teamPerformance,
            'source_breakdown' => $sourceBreakdown,
            'loss_reasons' => $lossReasons,
            'alerts' => $alerts,
            'goal_scoreboard' => $goalScoreboard,
            'insights' => $insights,
            'empty' => $summary['total'] === 0,
        ];
    }

    protected function normalizeRange(Carbon $from, Carbon $until): array
    {
        if ($from->greaterThan($until)) {
            [$from, $until] = [$until->copy(), $from->copy()];
        }

        return [$from->copy()->startOfDay(), $until->copy()->endOfDay()];
    }

    protected function loadCohortLeads(User $viewer, Carbon $from, Carbon $until, ?int $commercialId = null): Collection
    {
        $historyTouchQuery = LeadHistory::query()
            ->selectRaw('MIN(changed_at)')
            ->whereColumn('lead_id', 'leads.id')
            ->where(function (Builder $query): void {
                $query
                    ->whereIn('event_key', self::CONTACT_HISTORY_EVENTS)
                    ->orWhere('status_to', Lead::STAGE_CONTACTADO);
            });

        $completedTouchQuery = Seguimiento::query()
            ->selectRaw('MIN(created_at)')
            ->whereColumn('lead_id', 'leads.id')
            ->whereNull('fecha_contacto')
            ->where('estado', Seguimiento::STATUS_COMPLETADO);

        return $this->filterByCommercial(
            Lead::query()
                ->visibleTo($viewer)
                ->select([
                    'id',
                    'fecha_ingreso',
                    'comercial_asignado_id',
                    'canal_origen',
                    'orientacion_dada',
                    'estado_pipeline',
                    'resultado_final',
                    'motivo_perdida',
                    'fecha_cierre',
                    'fecha_ultimo_seguimiento',
                ])
                ->with('comercialAsignado:id,name')
                ->withExists([
                    'ventas as has_sales' => fn (Builder $query): Builder => $query,
                    'presupuestos as has_budget_record' => fn (Builder $query): Builder => $query,
                    'seguimientos as has_completed_touch' => function (Builder $query): void {
                        $query->where(function (Builder $touchQuery): void {
                            $touchQuery
                                ->whereNotNull('fecha_contacto')
                                ->orWhere('estado', Seguimiento::STATUS_COMPLETADO);
                        });
                    },
                    'histories as reached_contacted_history' => fn (Builder $query): Builder => $query->where('status_to', Lead::STAGE_CONTACTADO),
                    'histories as reached_orientation_history' => fn (Builder $query): Builder => $query->where('status_to', Lead::STAGE_ORIENTACION),
                    'histories as reached_quoted_history' => fn (Builder $query): Builder => $query->whereIn('status_to', [Lead::STAGE_COTIZACION, Lead::STAGE_PRESUPUESTO]),
                    'histories as reached_budget_history' => fn (Builder $query): Builder => $query->where('status_to', Lead::STAGE_PRESUPUESTO),
                ])
                ->withSum('ventas as revenue_total', 'monto_total')
                ->withMin([
                    'seguimientos as first_contact_date' => fn (Builder $query): Builder => $query->whereNotNull('fecha_contacto'),
                ], 'fecha_contacto')
                ->selectSub($completedTouchQuery, 'first_completed_touch_at')
                ->selectSub($historyTouchQuery, 'first_history_touch_at')
                ->whereBetween('fecha_ingreso', [$from->toDateString(), $until->toDateString()])
                ->orderBy('fecha_ingreso'),
            $commercialId,
        )->get();
    }

    protected function decorateLeadMetrics(Collection $leads): Collection
    {
        return $leads->map(function (Lead $lead): Lead {
            $lead->setAttribute('_metrics_contacted', $this->computeReachedContacted($lead));
            $lead->setAttribute('_metrics_orientation', $this->computeReachedOrientation($lead));
            $lead->setAttribute('_metrics_quoted', $this->computeReachedQuoted($lead));
            $lead->setAttribute('_metrics_budget', $this->computeReachedBudget($lead));
            $lead->setAttribute('_metrics_won', $this->computeWon($lead));
            $lead->setAttribute('_metrics_lost', $this->computeLost($lead));
            $lead->setAttribute('_metrics_revenue', $this->computeLeadRevenue($lead));
            $lead->setAttribute('_metrics_first_contact_days', $this->computeDaysToFirstContact($lead));

            return $lead;
        });
    }

    protected function buildSummary(Collection $leads): array
    {
        $total = $leads->count();
        $contacted = $leads->filter(fn (Lead $lead): bool => $this->hasReachedContacted($lead))->count();
        $quoted = $leads->filter(fn (Lead $lead): bool => $this->hasReachedQuoted($lead))->count();
        $won = $leads->filter(fn (Lead $lead): bool => $this->isWon($lead))->count();
        $lost = $leads->filter(fn (Lead $lead): bool => $this->isLost($lead))->count();
        $revenue = $leads->sum(fn (Lead $lead): float => $this->leadRevenue($lead));
        $avgTicket = $won > 0 ? $revenue / $won : 0.0;

        $avgFirstContactDays = round((float) $leads
            ->map(fn (Lead $lead): ?float => $this->daysToFirstContact($lead))
            ->filter(fn (?float $value): bool => $value !== null)
            ->avg(), 1);

        $avgCloseDays = round((float) $leads
            ->filter(fn (Lead $lead): bool => $this->isWon($lead) && filled($lead->fecha_cierre))
            ->map(fn (Lead $lead): float => (float) $lead->fecha_ingreso->diffInDays($lead->fecha_cierre))
            ->avg(), 1);

        return [
            'total' => $total,
            'contacted' => $contacted,
            'quoted' => $quoted,
            'won' => $won,
            'lost' => $lost,
            'revenue_raw' => (float) $revenue,
            'avg_ticket_raw' => (float) $avgTicket,
            'avg_first_contact_days_raw' => $avgFirstContactDays,
            'avg_close_days_raw' => $avgCloseDays,
            'contact_rate_raw' => $this->percent($contacted, $total),
            'quote_rate_raw' => $this->percent($quoted, $total),
            'close_rate_raw' => $this->percent($won, $total),
            'loss_rate_raw' => $this->percent($lost, $total),
        ];
    }

    protected function buildOverviewCards(array $summary): array
    {
        return [
            [
                'label' => 'Leads captados',
                'value' => $this->formatNumber($summary['total']),
                'note' => 'Oportunidades ingresadas dentro del periodo filtrado.',
                'tone' => 'primary',
            ],
            [
                'label' => 'Clientes contactados',
                'value' => $this->formatNumber($summary['contacted']),
                'note' => $this->formatPercent($summary['contact_rate_raw']) . ' del total con gestion efectiva.',
                'tone' => 'success',
            ],
            [
                'label' => 'Cotizaciones activadas',
                'value' => $this->formatNumber($summary['quoted']),
                'note' => $this->formatPercent($summary['quote_rate_raw']) . ' ya llego a instancia de propuesta.',
                'tone' => 'info',
            ],
            [
                'label' => 'Ventas cerradas',
                'value' => $this->formatNumber($summary['won']),
                'note' => $this->formatPercent($summary['close_rate_raw']) . ' de conversion final del embudo.',
                'tone' => 'emerald',
            ],
            [
                'label' => 'Ingresos cerrados',
                'value' => $this->formatMoney($summary['revenue_raw']),
                'note' => 'Ticket promedio ' . $this->formatMoney($summary['avg_ticket_raw']) . '.',
                'tone' => 'blue',
            ],
            [
                'label' => 'Tiempo de primer contacto',
                'value' => $summary['avg_first_contact_days_raw'] > 0 ? number_format($summary['avg_first_contact_days_raw'], 1, ',', '.') . ' dias' : 'Sin dato',
                'note' => $summary['avg_close_days_raw'] > 0
                    ? 'Cierre medio en ' . number_format($summary['avg_close_days_raw'], 1, ',', '.') . ' dias.'
                    : 'Todavia no hay cierres suficientes para medir velocidad.',
                'tone' => 'amber',
            ],
        ];
    }

    protected function buildFunnel(Collection $leads): array
    {
        $total = max($leads->count(), 1);
        $previousCount = $leads->count();
        $rows = [];

        foreach (self::FUNNEL_STAGES as $index => $stage) {
            $count = $index === 0
                ? $leads->count()
                : $leads->filter(fn (Lead $lead): bool => $this->hasReachedStage($lead, $stage))->count();

            $rows[] = [
                'label' => Lead::getPipelineLabel($stage),
                'count' => $this->formatNumber($count),
                'share' => $this->formatPercent($this->percent($count, $total)),
                'step_conversion' => $index === 0
                    ? 'Base del periodo'
                    : $this->formatPercent($this->percent($count, max($previousCount, 1))) . ' avanza desde la etapa anterior',
                'drop_off' => $index === 0 ? 0 : max($previousCount - $count, 0),
                'width' => max(($count / $total) * 100, $count > 0 ? 12 : 0),
                'tone' => Lead::getPipelineColor($stage),
            ];

            $previousCount = $count;
        }

        return $rows;
    }

    protected function buildTeamPerformance(Collection $leads): array
    {
        $rows = $leads
            ->groupBy(fn (Lead $lead): string => $lead->comercialAsignado?->name ?: 'Sin asignar')
            ->map(function (Collection $group, string $name): array {
                $total = $group->count();
                $contacted = $group->filter(fn (Lead $lead): bool => $this->hasReachedContacted($lead))->count();
                $quoted = $group->filter(fn (Lead $lead): bool => $this->hasReachedQuoted($lead))->count();
                $won = $group->filter(fn (Lead $lead): bool => $this->isWon($lead))->count();
                $lost = $group->filter(fn (Lead $lead): bool => $this->isLost($lead))->count();
                $stale = $group->filter(fn (Lead $lead): bool => $lead->dias_sin_seguimiento > 3 && ! in_array($lead->estado_pipeline, Lead::CLOSED_PIPELINES, true))->count();
                $revenue = $group->sum(fn (Lead $lead): float => $this->leadRevenue($lead));

                return [
                    'name' => $name,
                    'leads' => $this->formatNumber($total),
                    'contacted' => $this->formatNumber($contacted),
                    'quoted' => $this->formatNumber($quoted),
                    'won' => $this->formatNumber($won),
                    'lost' => $this->formatNumber($lost),
                    'stale' => $this->formatNumber($stale),
                    'conversion' => $this->formatPercent($this->percent($won, $total)),
                    'contact_rate' => $this->formatPercent($this->percent($contacted, $total)),
                    'revenue' => $this->formatMoney($revenue),
                    'won_raw' => $won,
                    'revenue_raw' => (float) $revenue,
                ];
            })
            ->sortByDesc(fn (array $row): float => ($row['won_raw'] * 1000000000) + $row['revenue_raw'])
            ->values()
            ->all();

        return array_map(function (array $row): array {
            unset($row['won_raw'], $row['revenue_raw']);

            return $row;
        }, $rows);
    }

    protected function buildSourceBreakdown(Collection $leads): array
    {
        return $leads
            ->groupBy(fn (Lead $lead): string => Lead::getCanalOrigenOptions()[$lead->canal_origen] ?? 'Sin origen')
            ->map(function (Collection $group, string $channel): array {
                $total = $group->count();
                $contacted = $group->filter(fn (Lead $lead): bool => $this->hasReachedContacted($lead))->count();
                $won = $group->filter(fn (Lead $lead): bool => $this->isWon($lead))->count();
                $revenue = $group->sum(fn (Lead $lead): float => $this->leadRevenue($lead));

                return [
                    'channel' => $channel,
                    'leads' => $this->formatNumber($total),
                    'contact_rate' => $this->formatPercent($this->percent($contacted, $total)),
                    'won' => $this->formatNumber($won),
                    'conversion' => $this->formatPercent($this->percent($won, $total)),
                    'revenue' => $this->formatMoney($revenue),
                    'won_raw' => $won,
                    'revenue_raw' => (float) $revenue,
                ];
            })
            ->sortByDesc(fn (array $row): float => ($row['won_raw'] * 1000000000) + $row['revenue_raw'])
            ->values()
            ->map(function (array $row): array {
                unset($row['won_raw'], $row['revenue_raw']);

                return $row;
            })
            ->all();
    }

    protected function buildLossReasons(Collection $leads): array
    {
        $lostLeads = $leads->filter(fn (Lead $lead): bool => $this->isLost($lead));
        $totalLost = $lostLeads->count();

        return $lostLeads
            ->groupBy(fn (Lead $lead): string => Lead::getMotivoPerdidaOptions()[$lead->motivo_perdida] ?? 'Sin motivo cargado')
            ->map(function (Collection $group, string $reason) use ($totalLost): array {
                $count = $group->count();

                return [
                    'reason' => $reason,
                    'count' => $this->formatNumber($count),
                    'share' => $this->formatPercent($this->percent($count, max($totalLost, 1))),
                    'width' => max(($count / max($totalLost, 1)) * 100, $count > 0 ? 10 : 0),
                    'count_raw' => $count,
                ];
            })
            ->sortByDesc('count_raw')
            ->values()
            ->map(function (array $row): array {
                unset($row['count_raw']);

                return $row;
            })
            ->all();
    }

    protected function buildOperationalAlerts(User $viewer, ?int $commercialId = null): array
    {
        $baseQuery = $this->filterByCommercial(
            Lead::query()->visibleTo($viewer),
            $commercialId,
        );

        $overdueFollowUps = (clone $baseQuery)
            ->openPipeline()
            ->whereHas('nextPendingSeguimiento', function (Builder $query): void {
                $query->whereDate('fecha_proxima_accion', '<', now()->toDateString());
            })
            ->count();

        $staleLeads = (clone $baseQuery)
            ->openPipeline()
            ->whereDate('fecha_ultimo_seguimiento', '<', now()->subDays(3)->toDateString())
            ->count();

        $quoteWithoutBudget = (clone $baseQuery)
            ->whereIn('estado_pipeline', [Lead::STAGE_COTIZACION, Lead::STAGE_PRESUPUESTO])
            ->doesntHave('presupuestos')
            ->count();

        $withoutOwner = (clone $baseQuery)
            ->whereNull('comercial_asignado_id')
            ->count();

        return [
            [
                'title' => 'Seguimientos vencidos',
                'value' => $this->formatNumber($overdueFollowUps),
                'detail' => 'Compromisos comerciales con fecha pasada y sin cerrar.',
                'tone' => $overdueFollowUps > 0 ? 'danger' : 'success',
            ],
            [
                'title' => 'Sin contacto reciente',
                'value' => $this->formatNumber($staleLeads),
                'detail' => 'Leads abiertos con mas de 3 dias sin movimiento real.',
                'tone' => $staleLeads > 0 ? 'warning' : 'success',
            ],
            [
                'title' => 'Cotizacion sin presupuesto',
                'value' => $this->formatNumber($quoteWithoutBudget),
                'detail' => 'Oportunidades avanzadas sin propuesta formal cargada.',
                'tone' => $quoteWithoutBudget > 0 ? 'warning' : 'success',
            ],
            [
                'title' => 'Sin responsable',
                'value' => $this->formatNumber($withoutOwner),
                'detail' => 'Leads visibles todavia sin vendedora o owner asignado.',
                'tone' => $withoutOwner > 0 ? 'danger' : 'success',
            ],
        ];
    }

    protected function buildGoalScoreboard(
        User $viewer,
        Carbon $monthStart,
        Carbon $monthEnd,
        ?int $commercialId = null,
        ?Collection $monthlyLeads = null,
    ): array {
        $monthlyLeads ??= $this->decorateLeadMetrics(
            $this->loadCohortLeads($viewer, $monthStart, $monthEnd, $commercialId),
        );

        $goalQuery = CommercialGoal::query()
            ->with('user:id,name')
            ->whereDate('goal_month', $monthStart->toDateString());

        if (filled($commercialId)) {
            $goalQuery->where('user_id', $commercialId);
        } elseif (! $viewer->canAccess('lead.view_all') && ! $viewer->canAccess('lead.assign')) {
            $goalQuery->where('user_id', $viewer->id);
        }

        $goals = $goalQuery->get()->keyBy('user_id');
        $groupedLeads = $monthlyLeads
            ->filter(fn (Lead $lead): bool => filled($lead->comercial_asignado_id))
            ->groupBy('comercial_asignado_id');

        $userIds = $groupedLeads->keys()
            ->merge($goals->keys())
            ->unique()
            ->values();

        $rows = $userIds->map(function ($userId) use ($groupedLeads, $goals): array {
            $goal = $goals->get($userId);
            $leadGroup = $groupedLeads->get($userId, collect());

            $actualLeads = $leadGroup->count();
            $actualContacts = $leadGroup->filter(fn (Lead $lead): bool => $this->hasReachedContacted($lead))->count();
            $actualQuotes = $leadGroup->filter(fn (Lead $lead): bool => $this->hasReachedQuoted($lead))->count();
            $actualSales = $leadGroup->filter(fn (Lead $lead): bool => $this->isWon($lead))->count();
            $actualRevenue = $leadGroup->sum(fn (Lead $lead): float => $this->leadRevenue($lead));

            $targetLeads = (int) ($goal?->target_leads ?? 0);
            $targetContacts = (int) ($goal?->target_contacts ?? 0);
            $targetQuotes = (int) ($goal?->target_quotes ?? 0);
            $targetSales = (int) ($goal?->target_sales ?? 0);
            $targetRevenue = (float) ($goal?->target_revenue ?? 0);
            $hasMeaningfulGoal = $targetLeads > 0 || $targetContacts > 0 || $targetQuotes > 0 || $targetSales > 0 || $targetRevenue > 0;

            $attainmentBaseTarget = ! $hasMeaningfulGoal
                ? 0.0
                : ($targetRevenue > 0
                    ? $this->percent($actualRevenue, $targetRevenue)
                    : ($targetSales > 0
                        ? $this->percent($actualSales, $targetSales)
                        : $this->percent($actualLeads, max($targetLeads, 1))));

            [$statusLabel, $statusTone] = match (true) {
                ! $goal || ! $hasMeaningfulGoal => ['Sin meta', 'gray'],
                $attainmentBaseTarget >= 100 => ['Cumplida', 'success'],
                $attainmentBaseTarget >= 80 => ['En curso', 'warning'],
                default => ['Debajo', 'danger'],
            };

            return [
                'name' => $goal?->user?->name ?? $leadGroup->first()?->comercialAsignado?->name ?? 'Sin asignar',
                'goal_leads' => $this->formatNumber($targetLeads),
                'actual_leads' => $this->formatNumber($actualLeads),
                'lead_delta' => $this->formatSignedNumber($actualLeads - $targetLeads),
                'goal_contacts' => $this->formatNumber($targetContacts),
                'actual_contacts' => $this->formatNumber($actualContacts),
                'contact_delta' => $this->formatSignedNumber($actualContacts - $targetContacts),
                'goal_quotes' => $this->formatNumber($targetQuotes),
                'actual_quotes' => $this->formatNumber($actualQuotes),
                'quote_delta' => $this->formatSignedNumber($actualQuotes - $targetQuotes),
                'goal_sales' => $this->formatNumber($targetSales),
                'actual_sales' => $this->formatNumber($actualSales),
                'sales_delta' => $this->formatSignedNumber($actualSales - $targetSales),
                'goal_revenue' => $this->formatMoney($targetRevenue),
                'actual_revenue' => $this->formatMoney($actualRevenue),
                'revenue_delta' => $this->formatSignedMoney($actualRevenue - $targetRevenue),
                'attainment' => $goal && $hasMeaningfulGoal ? $this->formatPercent($attainmentBaseTarget) : 'Sin meta',
                'status_label' => $statusLabel,
                'status_tone' => $statusTone,
                'notes' => $goal?->notes,
                'has_goal' => (bool) $goal,
                'actual_leads_raw' => $actualLeads,
                'target_leads_raw' => $targetLeads,
                'target_sales_raw' => $targetSales,
                'target_revenue_raw' => $targetRevenue,
                'actual_sales_raw' => $actualSales,
                'actual_revenue_raw' => (float) $actualRevenue,
            ];
        })->sortByDesc(fn (array $row): float => ($row['actual_sales_raw'] * 1000000000) + $row['actual_revenue_raw'])->values();

        $targetRevenueTotal = $rows->sum('target_revenue_raw');
        $actualRevenueTotal = $rows->sum('actual_revenue_raw');
        $targetSalesTotal = $rows->sum('target_sales_raw');
        $actualSalesTotal = $rows->sum('actual_sales_raw');
        $targetLeadsTotal = $rows->sum('target_leads_raw');
        $actualLeadsTotal = $rows->sum('actual_leads_raw');

        $summaryCards = [
            [
                'label' => 'Meta de leads',
                'value' => $this->formatNumber($targetLeadsTotal),
                'note' => 'Real: ' . $this->formatNumber($actualLeadsTotal),
            ],
            [
                'label' => 'Meta de ventas',
                'value' => $this->formatNumber($targetSalesTotal),
                'note' => 'Real: ' . $this->formatNumber($actualSalesTotal),
            ],
            [
                'label' => 'Meta de facturacion',
                'value' => $this->formatMoney($targetRevenueTotal),
                'note' => 'Real: ' . $this->formatMoney($actualRevenueTotal),
            ],
            [
                'label' => 'Cumplimiento global',
                'value' => $targetRevenueTotal > 0
                    ? $this->formatPercent($this->percent($actualRevenueTotal, $targetRevenueTotal))
                    : ($targetSalesTotal > 0
                        ? $this->formatPercent($this->percent($actualSalesTotal, $targetSalesTotal))
                        : 'Sin meta'),
                'note' => 'Mes objetivo ' . $monthStart->format('m/Y'),
            ],
        ];

        $rows = $rows->map(function (array $row): array {
            unset(
                $row['actual_leads_raw'],
                $row['target_leads_raw'],
                $row['target_sales_raw'],
                $row['target_revenue_raw'],
                $row['actual_sales_raw'],
                $row['actual_revenue_raw'],
            );

            return $row;
        })->all();

        return [
            'month_label' => $monthStart->format('m/Y'),
            'rows' => $rows,
            'summary_cards' => $summaryCards,
            'has_goals' => $goals->isNotEmpty(),
        ];
    }

    protected function buildExecutiveInsights(
        array $summary,
        array $teamPerformance,
        array $sourceBreakdown,
        array $lossReasons,
        array $alerts,
    ): array {
        if ($summary['total'] === 0) {
            return [
                [
                    'title' => 'Sin actividad en el filtro actual',
                    'description' => 'No hay leads para medir en el rango seleccionado. Ajusta fechas o responsable para ampliar la lectura.',
                    'tone' => 'neutral',
                ],
            ];
        }

        $insights = [];
        $bestSeller = collect($teamPerformance)->first();
        $bestChannel = collect($sourceBreakdown)->first();
        $topLoss = collect($lossReasons)->first();
        $activeAlert = collect($alerts)->first(fn (array $alert): bool => $alert['tone'] !== 'success');

        if ($bestSeller) {
            $insights[] = [
                'title' => 'Responsable con mejor cierre',
                'description' => $bestSeller['name'] . ' lidera el periodo con ' . $bestSeller['won'] . ' ventas, ' . $bestSeller['conversion'] . ' de conversion y ' . $bestSeller['revenue'] . ' cerrados.',
                'tone' => 'success',
            ];
        }

        if ($topLoss) {
            $insights[] = [
                'title' => 'Principal causa de fuga',
                'description' => $topLoss['reason'] . ' concentra ' . $topLoss['share'] . ' de las oportunidades perdidas. Conviene revisar guion, precio y objeciones en esa franja.',
                'tone' => 'warning',
            ];
        }

        if ($bestChannel) {
            $insights[] = [
                'title' => 'Canal con mejor rendimiento',
                'description' => $bestChannel['channel'] . ' aporta ' . $bestChannel['conversion'] . ' de conversion y ' . $bestChannel['revenue'] . ' cerrados en el periodo.',
                'tone' => 'primary',
            ];
        }

        if ($activeAlert && count($insights) < 3) {
            $insights[] = [
                'title' => 'Alerta operativa inmediata',
                'description' => $activeAlert['title'] . ': ' . $activeAlert['value'] . '. ' . $activeAlert['detail'],
                'tone' => 'danger',
            ];
        }

        return array_slice($insights, 0, 3);
    }

    protected function filterByCommercial(Builder $query, ?int $commercialId = null): Builder
    {
        return $query->when(
            filled($commercialId),
            fn (Builder $builder): Builder => $builder->where('comercial_asignado_id', $commercialId),
        );
    }

    protected function resolveScopeLabel(?int $commercialId = null): string
    {
        if (! filled($commercialId)) {
            return 'Toda la cartera visible';
        }

        $commercial = User::query()
            ->select(['id', 'name'])
            ->find($commercialId);

        return $commercial?->name
            ? 'Responsable: ' . $commercial->name
            : 'Responsable filtrado';
    }

    protected function hasReachedStage(Lead $lead, string $stage): bool
    {
        return match ($stage) {
            Lead::STAGE_INGRESADO => true,
            Lead::STAGE_CONTACTADO => $this->hasReachedContacted($lead),
            Lead::STAGE_ORIENTACION => $this->hasReachedOrientation($lead),
            Lead::STAGE_COTIZACION => $this->hasReachedQuoted($lead),
            Lead::STAGE_PRESUPUESTO => $this->hasReachedBudget($lead),
            Lead::STAGE_VENTA_CERRADA => $this->isWon($lead),
            default => false,
        };
    }

    protected function hasReachedContacted(Lead $lead): bool
    {
        if ($this->hasCachedMetric($lead, '_metrics_contacted')) {
            return (bool) $this->cachedMetric($lead, '_metrics_contacted');
        }

        return $this->computeReachedContacted($lead);
    }

    protected function hasReachedOrientation(Lead $lead): bool
    {
        if ($this->hasCachedMetric($lead, '_metrics_orientation')) {
            return (bool) $this->cachedMetric($lead, '_metrics_orientation');
        }

        return $this->computeReachedOrientation($lead);
    }

    protected function hasReachedQuoted(Lead $lead): bool
    {
        if ($this->hasCachedMetric($lead, '_metrics_quoted')) {
            return (bool) $this->cachedMetric($lead, '_metrics_quoted');
        }

        return $this->computeReachedQuoted($lead);
    }

    protected function hasReachedBudget(Lead $lead): bool
    {
        if ($this->hasCachedMetric($lead, '_metrics_budget')) {
            return (bool) $this->cachedMetric($lead, '_metrics_budget');
        }

        return $this->computeReachedBudget($lead);
    }

    protected function isWon(Lead $lead): bool
    {
        if ($this->hasCachedMetric($lead, '_metrics_won')) {
            return (bool) $this->cachedMetric($lead, '_metrics_won');
        }

        return $this->computeWon($lead);
    }

    protected function isLost(Lead $lead): bool
    {
        if ($this->hasCachedMetric($lead, '_metrics_lost')) {
            return (bool) $this->cachedMetric($lead, '_metrics_lost');
        }

        return $this->computeLost($lead);
    }

    protected function daysToFirstContact(Lead $lead): ?float
    {
        if ($this->hasCachedMetric($lead, '_metrics_first_contact_days')) {
            return $this->cachedMetric($lead, '_metrics_first_contact_days');
        }

        return $this->computeDaysToFirstContact($lead);
    }

    protected function leadRevenue(Lead $lead): float
    {
        if ($this->hasCachedMetric($lead, '_metrics_revenue')) {
            return (float) $this->cachedMetric($lead, '_metrics_revenue');
        }

        return $this->computeLeadRevenue($lead);
    }

    protected function computeReachedContacted(Lead $lead): bool
    {
        return $this->hasCompletedTouch($lead)
            || $lead->estado_pipeline !== Lead::STAGE_INGRESADO
            || $this->historyShowsStage($lead, Lead::STAGE_CONTACTADO);
    }

    protected function computeReachedOrientation(Lead $lead): bool
    {
        if ($lead->orientacion_dada) {
            return true;
        }

        if (in_array($lead->estado_pipeline, [
            Lead::STAGE_ORIENTACION,
            Lead::STAGE_COTIZACION,
            Lead::STAGE_PRESUPUESTO,
            Lead::STAGE_VENTA_CERRADA,
        ], true)) {
            return true;
        }

        return $this->historyShowsStage($lead, Lead::STAGE_ORIENTACION);
    }

    protected function computeReachedQuoted(Lead $lead): bool
    {
        if ($this->hasBudgetRecord($lead)) {
            return true;
        }

        if (in_array($lead->estado_pipeline, [
            Lead::STAGE_COTIZACION,
            Lead::STAGE_PRESUPUESTO,
            Lead::STAGE_VENTA_CERRADA,
        ], true)) {
            return true;
        }

        return $this->historyShowsStage($lead, Lead::STAGE_COTIZACION)
            || $this->historyShowsStage($lead, Lead::STAGE_PRESUPUESTO);
    }

    protected function computeReachedBudget(Lead $lead): bool
    {
        if ($this->hasBudgetRecord($lead)) {
            return true;
        }

        if (in_array($lead->estado_pipeline, [
            Lead::STAGE_PRESUPUESTO,
            Lead::STAGE_VENTA_CERRADA,
        ], true)) {
            return true;
        }

        return $this->historyShowsStage($lead, Lead::STAGE_PRESUPUESTO);
    }

    protected function computeWon(Lead $lead): bool
    {
        return $lead->resultado_final === Lead::RESULTADO_VENDIDO
            || $lead->estado_pipeline === Lead::STAGE_VENTA_CERRADA
            || $this->hasSales($lead);
    }

    protected function computeLost(Lead $lead): bool
    {
        return $lead->resultado_final === Lead::RESULTADO_PERDIDO
            || $lead->estado_pipeline === Lead::STAGE_PERDIDO;
    }

    protected function computeDaysToFirstContact(Lead $lead): ?float
    {
        $firstTouch = collect([
            $this->toCarbon($lead->first_contact_date),
            $this->toCarbon($lead->first_completed_touch_at),
            $this->toCarbon($lead->first_history_touch_at),
        ])
            ->filter()
            ->sortBy(fn (Carbon $date): int => $date->getTimestamp())
            ->first();

        if (! $firstTouch || blank($lead->fecha_ingreso)) {
            return null;
        }

        return (float) $lead->fecha_ingreso->diffInDays($firstTouch);
    }

    protected function computeLeadRevenue(Lead $lead): float
    {
        return (float) ($lead->revenue_total ?? 0);
    }

    protected function hasCompletedTouch(Lead $lead): bool
    {
        return (bool) ($lead->has_completed_touch ?? false);
    }

    protected function hasSales(Lead $lead): bool
    {
        return (bool) ($lead->has_sales ?? false);
    }

    protected function hasBudgetRecord(Lead $lead): bool
    {
        return (bool) ($lead->has_budget_record ?? false);
    }

    protected function historyShowsStage(Lead $lead, string $stage): bool
    {
        return match ($stage) {
            Lead::STAGE_CONTACTADO => (bool) ($lead->reached_contacted_history ?? false),
            Lead::STAGE_ORIENTACION => (bool) ($lead->reached_orientation_history ?? false),
            Lead::STAGE_COTIZACION => (bool) ($lead->reached_quoted_history ?? false),
            Lead::STAGE_PRESUPUESTO => (bool) ($lead->reached_budget_history ?? false),
            default => false,
        };
    }

    protected function hasCachedMetric(Lead $lead, string $key): bool
    {
        return array_key_exists($key, $lead->getAttributes());
    }

    protected function cachedMetric(Lead $lead, string $key): mixed
    {
        return $lead->getAttributes()[$key] ?? null;
    }

    protected function toCarbon(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        return $value instanceof Carbon
            ? $value
            : Carbon::parse($value);
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

    protected function formatPercent(int|float $value): string
    {
        return number_format($value, 1, ',', '.') . '%';
    }

    protected function formatMoney(int|float $value): string
    {
        return '$ ' . number_format($value, 0, ',', '.');
    }

    protected function formatSignedMoney(int|float $value): string
    {
        $prefix = $value > 0 ? '+' : ($value < 0 ? '-' : '');

        return $prefix . '$ ' . number_format(abs($value), 0, ',', '.');
    }

    protected function formatSignedNumber(int|float $value): string
    {
        $prefix = $value > 0 ? '+' : ($value < 0 ? '-' : '');

        return $prefix . number_format(abs($value), 0, ',', '.');
    }
}
