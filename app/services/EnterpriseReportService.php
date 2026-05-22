<?php

namespace App\Services;

use App\Models\BillingControl;
use App\Models\Claim;
use App\Models\Client;
use App\Models\Conservation;
use App\Models\ConservationRenewal;
use App\Models\ConservationVisit;
use App\Models\Habilitation;
use App\Models\Lead;
use App\Models\Pendiente;
use App\Models\ProductoVariante;
use App\Models\ServiceVisit;
use App\Models\ServiceVisitEvent;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class EnterpriseReportService
{
    public function __construct(
        protected CommercialMetricsService $commercialMetrics,
    ) {
    }

    public function build(User $viewer, Carbon $from, Carbon $until): array
    {
        [$from, $until] = $this->normalizeRange($from, $until);

        $commercial = $this->buildCommercialSnapshot($viewer, $from, $until);
        $operations = $this->buildOperationsSnapshot($from, $until);
        $postSale = $this->buildPostSaleSnapshot($from, $until);
        $conservations = $this->buildConservationsSnapshot($from, $until);
        $billing = $this->buildBillingSnapshot($from, $until);
        $inventory = $this->buildInventorySnapshot($from, $until);
        $habilitations = $this->buildHabilitationsSnapshot($from, $until);
        $clients = $this->buildClientSnapshot($from, $until);

        $overviewCards = $this->buildOverviewCards(
            clients: $clients,
            commercial: $commercial,
            operations: $operations,
            postSale: $postSale,
            conservations: $conservations,
            billing: $billing,
            inventory: $inventory,
            habilitations: $habilitations,
        );

        $companyPulse = $this->buildCompanyPulse(
            commercial: $commercial,
            operations: $operations,
            postSale: $postSale,
            conservations: $conservations,
            billing: $billing,
            inventory: $inventory,
            habilitations: $habilitations,
        );

        $alerts = $this->buildAlerts(
            commercial: $commercial,
            operations: $operations,
            postSale: $postSale,
            conservations: $conservations,
            billing: $billing,
            inventory: $inventory,
            habilitations: $habilitations,
        );

        $insights = $this->buildInsights(
            commercial: $commercial,
            operations: $operations,
            postSale: $postSale,
            conservations: $conservations,
            billing: $billing,
            inventory: $inventory,
            habilitations: $habilitations,
            clients: $clients,
        );

        return [
            'hero' => [
                'eyebrow' => 'Pulso del ERP',
                'title' => 'Informes ejecutivos para leer comercial, operacion, postventa, cobranzas e inventario desde un solo tablero.',
                'subtitle' => 'Vista de gerencia para detectar saturacion operativa, ritmo comercial, contratos a renovar, plata inmovilizada y faltantes criticos sin ir modulo por modulo.',
                'period_label' => $from->format('d/m/Y') . ' al ' . $until->format('d/m/Y'),
                'scope_label' => 'Estado general del ERP',
                'chips' => [
                    [
                        'label' => 'Ventas del periodo',
                        'value' => $this->formatMoney($commercial['revenue_period_raw']),
                    ],
                    [
                        'label' => 'Operacion abierta',
                        'value' => $this->formatNumber($operations['open_pendings_raw'] + $operations['active_visits_raw']),
                    ],
                    [
                        'label' => 'Cobranza pendiente',
                        'value' => $this->formatMoney($billing['outstanding_amount_raw']),
                    ],
                    [
                        'label' => 'Contratos por renovar',
                        'value' => $this->formatNumber($conservations['renewal_required_raw']),
                    ],
                ],
            ],
            'overview_cards' => $overviewCards,
            'company_pulse' => $companyPulse,
            'insights' => $insights,
            'alerts' => $alerts,
            'commercial' => $commercial,
            'operations' => $operations,
            'post_sale' => $postSale,
            'conservations' => $conservations,
            'billing' => $billing,
            'inventory' => $inventory,
            'habilitations' => $habilitations,
            'clients' => $clients,
        ];
    }

    protected function normalizeRange(Carbon $from, Carbon $until): array
    {
        if ($from->greaterThan($until)) {
            [$from, $until] = [$until->copy(), $from->copy()];
        }

        return [$from->copy()->startOfDay(), $until->copy()->endOfDay()];
    }

    protected function buildClientSnapshot(Carbon $from, Carbon $until): array
    {
        $totalClients = Client::query()->count();
        $newClients = Client::query()
            ->whereBetween('created_at', [$from, $until])
            ->count();

        $openPendingCounts = Pendiente::query()
            ->selectRaw('client_id, COUNT(*) as total')
            ->whereNotNull('client_id')
            ->whereIn('status', [Pendiente::STATUS_PENDING, Pendiente::STATUS_IN_PROGRESS])
            ->groupBy('client_id')
            ->pluck('total', 'client_id');

        $openClaimStatuses = ['nuevo', 'en_proceso', 'pendiente'];
        $openClaimCounts = Claim::query()
            ->selectRaw('client_id, COUNT(*) as total')
            ->whereNotNull('client_id')
            ->whereIn('status', $openClaimStatuses)
            ->groupBy('client_id')
            ->pluck('total', 'client_id');

        $outstandingAmounts = BillingControl::query()
            ->selectRaw('client_id, SUM(amount) as total')
            ->whereNotNull('client_id')
            ->where('invoiced', true)
            ->where('paid', false)
            ->groupBy('client_id')
            ->pluck('total', 'client_id');

        $activeConservationCounts = Conservation::query()
            ->selectRaw('client_id, COUNT(*) as total')
            ->whereNotNull('client_id')
            ->where('contract_status', Conservation::STATUS_ACTIVE)
            ->groupBy('client_id')
            ->pluck('total', 'client_id');

        $openHabilitationStatuses = ['pendiente', 'documentacion', 'enviado_gestor', 'en_tramite'];
        $openHabilitationCounts = Habilitation::query()
            ->selectRaw('client_id, COUNT(*) as total')
            ->whereNotNull('client_id')
            ->whereIn('status', $openHabilitationStatuses)
            ->groupBy('client_id')
            ->pluck('total', 'client_id');

        $clientsWithOpenPendings = $openPendingCounts->count();
        $clientsWithOpenClaims = $openClaimCounts->count();
        $clientsWithOutstanding = $outstandingAmounts->count();
        $recurringClients = $activeConservationCounts->count();
        $clientsWithOpenHabilitations = $openHabilitationCounts->count();
        $clientsWithActiveWorkflows = collect([
            $openPendingCounts->keys()->all(),
            $openClaimCounts->keys()->all(),
            $outstandingAmounts->keys()->all(),
            $openHabilitationCounts->keys()->all(),
        ])->flatten()->unique()->count();

        $portfolioRows = Client::query()
            ->select(['id', 'name'])
            ->get()
            ->map(function (Client $client) use (
                $openPendingCounts,
                $openClaimCounts,
                $outstandingAmounts,
                $activeConservationCounts,
                $openHabilitationCounts
            ): array {
                $openPendings = (int) ($openPendingCounts[$client->id] ?? 0);
                $openClaims = (int) ($openClaimCounts[$client->id] ?? 0);
                $outstanding = (float) ($outstandingAmounts[$client->id] ?? 0);
                $recurring = (int) ($activeConservationCounts[$client->id] ?? 0);
                $habilitations = (int) ($openHabilitationCounts[$client->id] ?? 0);

                $pressureScore = ($openPendings * 4)
                    + ($openClaims * 5)
                    + ($habilitations * 3)
                    + (int) floor($outstanding / 50000)
                    + min($recurring, 3);

                $hasActivity = $openPendings > 0
                    || $openClaims > 0
                    || $outstanding > 0
                    || $recurring > 0
                    || $habilitations > 0;

                if ($openClaims > 0 || $openPendings >= 3 || $outstanding >= 300000) {
                    $focusTone = 'danger';
                } elseif ($openPendings > 0 || $habilitations > 0 || $outstanding > 0) {
                    $focusTone = 'warning';
                } else {
                    $focusTone = 'success';
                }

                $focusLabel = match (true) {
                    $openClaims > 0 => 'Postventa sensible',
                    $openPendings > 0 => 'Operacion activa',
                    $outstanding > 0 => 'Cobranza activa',
                    $habilitations > 0 => 'Gestion administrativa',
                    $recurring > 0 => 'Cartera recurrente',
                    default => 'Sin frentes abiertos',
                };

                return [
                    'name' => $client->name ?: 'Sin nombre',
                    'open_pendings' => $this->formatNumber($openPendings),
                    'open_claims' => $this->formatNumber($openClaims),
                    'outstanding' => $this->formatMoney($outstanding),
                    'recurring' => $this->formatNumber($recurring),
                    'open_habilitations' => $this->formatNumber($habilitations),
                    'focus_label' => $focusLabel,
                    'focus_tone' => $focusTone,
                    'has_activity' => $hasActivity,
                    'pressure_raw' => $pressureScore,
                ];
            })
            ->filter(fn (array $row): bool => $row['has_activity'])
            ->sortByDesc('pressure_raw')
            ->take(8)
            ->values()
            ->map(function (array $row): array {
                unset($row['has_activity'], $row['pressure_raw']);

                return $row;
            })
            ->all();

        $attentionRows = collect([
            [
                'label' => 'Clientes con operacion abierta',
                'count_raw' => $clientsWithOpenPendings,
                'tone' => $clientsWithOpenPendings > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Clientes con reclamos abiertos',
                'count_raw' => $clientsWithOpenClaims,
                'tone' => $clientsWithOpenClaims > 0 ? 'danger' : 'success',
            ],
            [
                'label' => 'Clientes con saldo pendiente',
                'count_raw' => $clientsWithOutstanding,
                'tone' => $clientsWithOutstanding > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Clientes con habilitaciones activas',
                'count_raw' => $clientsWithOpenHabilitations,
                'tone' => $clientsWithOpenHabilitations > 0 ? 'info' : 'success',
            ],
            [
                'label' => 'Clientes con contratos recurrentes',
                'count_raw' => $recurringClients,
                'tone' => 'success',
            ],
        ])->map(function (array $row) use ($totalClients): array {
            $share = $this->percent($row['count_raw'], max($totalClients, 1));

            return [
                'label' => $row['label'],
                'count' => $this->formatNumber($row['count_raw']),
                'share' => $this->formatPercent($share),
                'width' => max($share, $row['count_raw'] > 0 ? 14 : 0),
                'tone' => $row['tone'],
                'count_raw' => $row['count_raw'],
            ];
        })->filter(fn (array $row): bool => $row['count_raw'] > 0)->values()->map(function (array $row): array {
            unset($row['count_raw']);

            return $row;
        })->all();

        return [
            'total_clients_raw' => $totalClients,
            'new_clients_raw' => $newClients,
            'clients_with_open_pendings_raw' => $clientsWithOpenPendings,
            'clients_with_open_claims_raw' => $clientsWithOpenClaims,
            'clients_with_outstanding_raw' => $clientsWithOutstanding,
            'recurring_clients_raw' => $recurringClients,
            'clients_with_open_habilitations_raw' => $clientsWithOpenHabilitations,
            'clients_with_active_workflows_raw' => $clientsWithActiveWorkflows,
            'portfolio_rows' => $portfolioRows,
            'attention_rows' => $attentionRows,
            'summary_cards' => [
                [
                    'label' => 'Base total',
                    'value' => $this->formatNumber($totalClients),
                    'note' => 'Clientes registrados en la cartera del ERP.',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Altas del periodo',
                    'value' => $this->formatNumber($newClients),
                    'note' => 'Crecimiento reciente de la base comercial y operativa.',
                    'tone' => 'emerald',
                ],
                [
                    'label' => 'Clientes con gestion abierta',
                    'value' => $this->formatNumber($clientsWithActiveWorkflows),
                    'note' => 'Entre operacion, reclamos, cobranza o habilitaciones.',
                    'tone' => $clientsWithActiveWorkflows > 0 ? 'warning' : 'success',
                ],
                [
                    'label' => 'Clientes recurrentes',
                    'value' => $this->formatNumber($recurringClients),
                    'note' => 'Con contratos de conservacion activos.',
                    'tone' => 'success',
                ],
                [
                    'label' => 'Clientes con saldo pendiente',
                    'value' => $this->formatNumber($clientsWithOutstanding),
                    'note' => 'Cartera que hoy requiere accion de cobranza.',
                    'tone' => $clientsWithOutstanding > 0 ? 'warning' : 'success',
                ],
            ],
        ];
    }

    protected function buildCommercialSnapshot(User $viewer, Carbon $from, Carbon $until): array
    {
        $leadBase = Lead::query()->visibleTo($viewer);
        $leadsPeriodBase = (clone $leadBase)
            ->whereBetween('fecha_ingreso', [$from->toDateString(), $until->toDateString()]);

        $newLeads = (clone $leadsPeriodBase)->count();
        $openPipeline = (clone $leadBase)->openPipeline()->count();
        $staleOpenLeads = (clone $leadBase)
            ->openPipeline()
            ->whereDate('fecha_ultimo_seguimiento', '<', now()->subDays(3)->toDateString())
            ->count();
        $lostLeads = (clone $leadBase)
            ->whereBetween('fecha_cierre', [$from->toDateString(), $until->toDateString()])
            ->where(function (Builder $query): void {
                $query
                    ->where('resultado_final', Lead::RESULTADO_PERDIDO)
                    ->orWhere('estado_pipeline', Lead::STAGE_PERDIDO);
            })
            ->count();

        $salesPeriod = Venta::query()
            ->whereBetween('fecha_cierre', [$from->toDateString(), $until->toDateString()]);

        $salesCount = (clone $salesPeriod)->count();
        $revenue = (float) ((clone $salesPeriod)->sum('monto_total') ?? 0);
        $avgTicket = $salesCount > 0 ? $revenue / $salesCount : 0.0;

        $commercialReport = $this->commercialMetrics->build($viewer, $from, $until, null);

        return [
            'new_leads_raw' => $newLeads,
            'open_pipeline_raw' => $openPipeline,
            'stale_open_leads_raw' => $staleOpenLeads,
            'lost_leads_raw' => $lostLeads,
            'sales_period_raw' => $salesCount,
            'revenue_period_raw' => $revenue,
            'avg_ticket_raw' => $avgTicket,
            'funnel' => $commercialReport['funnel'],
            'team_performance' => $commercialReport['team_performance'],
            'source_breakdown' => $commercialReport['source_breakdown'],
            'loss_reasons' => $commercialReport['loss_reasons'],
            'overview_cards' => array_slice($commercialReport['overview_cards'], 0, 4),
        ];
    }

    protected function buildOperationsSnapshot(Carbon $from, Carbon $until): array
    {
        $relevantPendings = Pendiente::query()
            ->select([
                'id',
                'type',
                'status',
                'priority',
                'due_date',
                'completed_at',
                'user_id',
            ])
            ->where(function (Builder $query) use ($from, $until): void {
                $query
                    ->whereIn('status', [Pendiente::STATUS_PENDING, Pendiente::STATUS_IN_PROGRESS])
                    ->orWhereBetween('completed_at', [$from, $until])
                    ->orWhereBetween('created_at', [$from, $until]);
            })
            ->get();

        $openPendings = $relevantPendings
            ->whereIn('status', [Pendiente::STATUS_PENDING, Pendiente::STATUS_IN_PROGRESS])
            ->count();
        $pendingOnly = $relevantPendings->where('status', Pendiente::STATUS_PENDING)->count();
        $inProgressPendings = $relevantPendings->where('status', Pendiente::STATUS_IN_PROGRESS)->count();
        $completedPendingsPeriod = $relevantPendings
            ->filter(function (Pendiente $pendiente) use ($from, $until): bool {
                if ($pendiente->status !== Pendiente::STATUS_COMPLETED || blank($pendiente->completed_at)) {
                    return false;
                }

                return $pendiente->completed_at->between($from, $until);
            })
            ->count();
        $overduePendings = $relevantPendings
            ->filter(function (Pendiente $pendiente): bool {
                if (! in_array($pendiente->status, [Pendiente::STATUS_PENDING, Pendiente::STATUS_IN_PROGRESS], true)) {
                    return false;
                }

                return filled($pendiente->due_date) && $pendiente->due_date->lt(now()->startOfDay());
            })
            ->count();

        $pendingTypeRows = collect(Pendiente::getTypeOptions())
            ->map(function (string $label, string $type) use ($relevantPendings, $completedPendingsPeriod): array {
                $group = $relevantPendings->where('type', $type);
                $open = $group->where('status', Pendiente::STATUS_PENDING)->count();
                $inProgress = $group->where('status', Pendiente::STATUS_IN_PROGRESS)->count();
                $completed = $group
                    ->filter(fn (Pendiente $pendiente): bool => $pendiente->status === Pendiente::STATUS_COMPLETED)
                    ->count();
                $overdue = $group->filter(function (Pendiente $pendiente): bool {
                    return in_array($pendiente->status, [Pendiente::STATUS_PENDING, Pendiente::STATUS_IN_PROGRESS], true)
                        && filled($pendiente->due_date)
                        && $pendiente->due_date->lt(now()->startOfDay());
                })->count();

                return [
                    'label' => $label,
                    'open' => $this->formatNumber($open),
                    'in_progress' => $this->formatNumber($inProgress),
                    'completed' => $this->formatNumber($completed),
                    'overdue' => $this->formatNumber($overdue),
                    'workload_raw' => $open + $inProgress,
                    'width' => max(($open + $inProgress) * 12, ($open + $inProgress) > 0 ? 18 : 0),
                ];
            })
            ->filter(fn (array $row): bool => $row['workload_raw'] > 0 || $row['completed'] !== '0')
            ->sortByDesc('workload_raw')
            ->values()
            ->map(function (array $row): array {
                unset($row['workload_raw']);

                return $row;
            })
            ->all();

        $relevantVisits = ServiceVisit::query()
            ->with('technician:id,name')
            ->select([
                'id',
                'technician_id',
                'status',
                'visit_type',
                'created_at',
                'arrival_time',
                'departure_time',
            ])
            ->where(function (Builder $query) use ($from, $until): void {
                $query
                    ->whereIn('status', [
                        ServiceVisit::STATUS_PENDING,
                        ServiceVisit::STATUS_ACCEPTED,
                        ServiceVisit::STATUS_IN_PROGRESS,
                    ])
                    ->orWhereBetween('departure_time', [$from, $until])
                    ->orWhereBetween('created_at', [$from, $until]);
            })
            ->get();

        $activeVisits = $relevantVisits
            ->whereIn('status', [
                ServiceVisit::STATUS_PENDING,
                ServiceVisit::STATUS_ACCEPTED,
                ServiceVisit::STATUS_IN_PROGRESS,
            ])
            ->count();
        $acceptedVisits = $relevantVisits->where('status', ServiceVisit::STATUS_ACCEPTED)->count();
        $inProgressVisits = $relevantVisits->where('status', ServiceVisit::STATUS_IN_PROGRESS)->count();
        $completedVisitsPeriod = $relevantVisits
            ->filter(function (ServiceVisit $visit) use ($from, $until): bool {
                if ($visit->status !== ServiceVisit::STATUS_COMPLETED) {
                    return false;
                }

                $endDate = $visit->departure_time ?: $visit->created_at;

                return $endDate?->between($from, $until) ?? false;
            })
            ->count();

        $travelEvents = ServiceVisitEvent::query()
            ->where('event_type', ServiceVisitEvent::TYPE_TRAVELING)
            ->whereBetween('created_at', [$from, $until])
            ->count();

        $technicianRows = $relevantVisits
            ->groupBy(fn (ServiceVisit $visit): string => $visit->technician?->name ?: 'Sin tecnico')
            ->map(function (Collection $group, string $name) use ($from, $until): array {
                $assigned = $group->where('status', ServiceVisit::STATUS_PENDING)->count();
                $accepted = $group->where('status', ServiceVisit::STATUS_ACCEPTED)->count();
                $inProgress = $group->where('status', ServiceVisit::STATUS_IN_PROGRESS)->count();
                $completed = $group->filter(function (ServiceVisit $visit) use ($from, $until): bool {
                    if ($visit->status !== ServiceVisit::STATUS_COMPLETED) {
                        return false;
                    }

                    $endDate = $visit->departure_time ?: $visit->created_at;

                    return $endDate?->between($from, $until) ?? false;
                })->count();

                return [
                    'name' => $name,
                    'assigned' => $this->formatNumber($assigned),
                    'accepted' => $this->formatNumber($accepted),
                    'in_progress' => $this->formatNumber($inProgress),
                    'completed' => $this->formatNumber($completed),
                    'load_raw' => $assigned + $accepted + $inProgress + $completed,
                ];
            })
            ->sortByDesc('load_raw')
            ->values()
            ->map(function (array $row): array {
                unset($row['load_raw']);

                return $row;
            })
            ->all();

        return [
            'open_pendings_raw' => $openPendings,
            'pending_only_raw' => $pendingOnly,
            'in_progress_pendings_raw' => $inProgressPendings,
            'completed_pendings_period_raw' => $completedPendingsPeriod,
            'overdue_pendings_raw' => $overduePendings,
            'active_visits_raw' => $activeVisits,
            'accepted_visits_raw' => $acceptedVisits,
            'in_progress_visits_raw' => $inProgressVisits,
            'completed_visits_period_raw' => $completedVisitsPeriod,
            'travel_events_period_raw' => $travelEvents,
            'pending_type_rows' => $pendingTypeRows,
            'technician_rows' => $technicianRows,
            'summary_cards' => [
                [
                    'label' => 'Pendientes abiertos',
                    'value' => $this->formatNumber($openPendings),
                    'note' => $this->formatNumber($overduePendings) . ' vencidos hoy.',
                    'tone' => $overduePendings > 0 ? 'warning' : 'success',
                ],
                [
                    'label' => 'Pendientes en proceso',
                    'value' => $this->formatNumber($inProgressPendings),
                    'note' => $this->formatNumber($pendingOnly) . ' esperan arranque o asignacion.',
                    'tone' => 'info',
                ],
                [
                    'label' => 'Visitas activas',
                    'value' => $this->formatNumber($activeVisits),
                    'note' => $this->formatNumber($acceptedVisits) . ' aceptadas y ' . $this->formatNumber($inProgressVisits) . ' en sitio.',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Cierres del periodo',
                    'value' => $this->formatNumber($completedVisitsPeriod),
                    'note' => $this->formatNumber($completedPendingsPeriod) . ' pendientes cerrados y ' . $this->formatNumber($travelEvents) . ' eventos de traslado.',
                    'tone' => 'emerald',
                ],
            ],
        ];
    }

    protected function buildPostSaleSnapshot(Carbon $from, Carbon $until): array
    {
        $claims = Claim::query()
            ->with(['client:id,name', 'technician:id,name'])
            ->select([
                'id',
                'client_id',
                'technician_id',
                'title',
                'status',
                'scheduled_visit',
                'closed_at',
                'updated_at',
            ])
            ->get();

        $openStatuses = ['nuevo', 'en_proceso', 'pendiente'];
        $closedStatuses = ['resuelto', 'cerrado'];

        $openClaims = $claims->whereIn('status', $openStatuses)->count();
        $overdueClaims = $claims->filter(function (Claim $claim) use ($openStatuses): bool {
            if (! in_array((string) $claim->status, $openStatuses, true) || blank($claim->scheduled_visit)) {
                return false;
            }

            return Carbon::parse($claim->scheduled_visit)->lt(today());
        })->count();
        $resolvedClaimsPeriod = $claims->filter(function (Claim $claim) use ($from, $until, $closedStatuses): bool {
            if (! in_array((string) $claim->status, $closedStatuses, true)) {
                return false;
            }

            $closureDate = filled($claim->closed_at)
                ? Carbon::parse($claim->closed_at)
                : $claim->updated_at;

            return $closureDate?->between($from, $until) ?? false;
        })->count();

        $statusRows = collect([
            'nuevo' => 'Nuevos',
            'en_proceso' => 'En proceso',
            'pendiente' => 'Pendientes',
            'resuelto' => 'Resueltos',
            'cerrado' => 'Cerrados',
        ])->map(function (string $label, string $status) use ($claims): array {
            $count = $claims->where('status', $status)->count();

            return [
                'label' => $label,
                'count' => $this->formatNumber($count),
                'width' => max($count * 12, $count > 0 ? 18 : 0),
                'count_raw' => $count,
            ];
        })->filter(fn (array $row): bool => $row['count_raw'] > 0)->values()->map(function (array $row): array {
            unset($row['count_raw']);

            return $row;
        })->all();

        $queueRows = $claims
            ->filter(fn (Claim $claim): bool => in_array((string) $claim->status, $openStatuses, true))
            ->sortBy(function (Claim $claim): int {
                return filled($claim->scheduled_visit)
                    ? Carbon::parse($claim->scheduled_visit)->getTimestamp()
                    : PHP_INT_MAX;
            })
            ->take(6)
            ->map(function (Claim $claim): array {
                $scheduled = filled($claim->scheduled_visit)
                    ? Carbon::parse($claim->scheduled_visit)
                    : null;

                return [
                    'title' => $claim->title ?: 'Reclamo #' . $claim->id,
                    'client' => $claim->client?->name ?? 'Sin cliente',
                    'status' => (string) str($claim->status)->replace('_', ' ')->title(),
                    'technician' => $claim->technician?->name ?? 'Sin tecnico',
                    'scheduled' => $scheduled?->format('d/m/Y') ?? 'Sin fecha',
                    'tone' => $scheduled && $scheduled->lt(today()) ? 'danger' : 'warning',
                ];
            })
            ->values()
            ->all();

        return [
            'open_claims_raw' => $openClaims,
            'overdue_claims_raw' => $overdueClaims,
            'resolved_claims_period_raw' => $resolvedClaimsPeriod,
            'status_rows' => $statusRows,
            'queue_rows' => $queueRows,
            'summary_cards' => [
                [
                    'label' => 'Reclamos abiertos',
                    'value' => $this->formatNumber($openClaims),
                    'note' => 'Entre nuevos, pendientes y en proceso.',
                    'tone' => $openClaims > 0 ? 'warning' : 'success',
                ],
                [
                    'label' => 'Visitas atrasadas',
                    'value' => $this->formatNumber($overdueClaims),
                    'note' => 'Reclamos con fecha superada y sin cierre.',
                    'tone' => $overdueClaims > 0 ? 'danger' : 'success',
                ],
                [
                    'label' => 'Resueltos en el periodo',
                    'value' => $this->formatNumber($resolvedClaimsPeriod),
                    'note' => 'Cierres de reclamos dentro del rango filtrado.',
                    'tone' => 'emerald',
                ],
            ],
        ];
    }

    protected function buildConservationsSnapshot(Carbon $from, Carbon $until): array
    {
        $contracts = Conservation::query()
            ->with('client:id,name')
            ->select([
                'id',
                'client_id',
                'frequency',
                'next_service_date',
                'expiration_date',
                'contract_status',
                'current_service_number',
                'total_services',
                'contract_cycle_number',
            ])
            ->get();

        $activeContracts = $contracts->filter(fn (Conservation $contract): bool => $contract->contract_status === Conservation::STATUS_ACTIVE)->count();
        $renewalRequired = $contracts->filter(fn (Conservation $contract): bool => $contract->canBeRenewed())->count();
        $dueThisPeriod = $contracts->filter(function (Conservation $contract) use ($from, $until): bool {
            return $contract->contract_status === Conservation::STATUS_ACTIVE
                && filled($contract->next_service_date)
                && $contract->next_service_date->between($from, $until);
        })->count();
        $overdueServices = $contracts->filter(function (Conservation $contract): bool {
            return $contract->contract_status === Conservation::STATUS_ACTIVE
                && filled($contract->next_service_date)
                && $contract->next_service_date->lt(today());
        })->count();

        $completedVisitsPeriod = ConservationVisit::query()
            ->whereBetween('date', [$from->toDateString(), $until->toDateString()])
            ->count();

        $renewalsPeriod = ConservationRenewal::query()
            ->whereBetween('renewed_at', [$from->toDateString(), $until->toDateString()])
            ->count();

        $frequencyRows = $contracts
            ->filter(fn (Conservation $contract): bool => $contract->contract_status === Conservation::STATUS_ACTIVE)
            ->groupBy(fn (Conservation $contract): string => (string) str($contract->frequency)->title())
            ->map(function (Collection $group, string $frequency) use ($activeContracts): array {
                $count = $group->count();

                return [
                    'label' => $frequency,
                    'count' => $this->formatNumber($count),
                    'share' => $this->formatPercent($this->percent($count, max($activeContracts, 1))),
                    'width' => max(($count / max($activeContracts, 1)) * 100, $count > 0 ? 15 : 0),
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

        $upcomingRows = $contracts
            ->filter(fn (Conservation $contract): bool => $contract->contract_status === Conservation::STATUS_ACTIVE && filled($contract->next_service_date))
            ->sortBy(fn (Conservation $contract): int => $contract->next_service_date->getTimestamp())
            ->take(6)
            ->map(function (Conservation $contract): array {
                return [
                    'client' => $contract->client?->name ?? 'Sin cliente',
                    'progress' => $contract->service_progress,
                    'frequency' => (string) str($contract->frequency)->title(),
                    'next_service' => $contract->next_service_date?->format('d/m/Y') ?? 'Sin fecha',
                    'tone' => $contract->next_service_date && $contract->next_service_date->lt(today()) ? 'danger' : 'warning',
                ];
            })
            ->values()
            ->all();

        $renewalRows = ConservationRenewal::query()
            ->with('conservation.client:id,name')
            ->whereBetween('renewed_at', [$from->toDateString(), $until->toDateString()])
            ->latest('renewed_at')
            ->take(6)
            ->get()
            ->map(function (ConservationRenewal $renewal): array {
                return [
                    'client' => $renewal->conservation?->client?->name ?? 'Sin cliente',
                    'cycle' => $renewal->previous_cycle_number . ' -> ' . $renewal->new_cycle_number,
                    'new_frequency' => (string) str($renewal->new_frequency)->title(),
                    'renewed_at' => $renewal->renewed_at?->format('d/m/Y') ?? 'Sin fecha',
                ];
            })
            ->values()
            ->all();

        return [
            'active_contracts_raw' => $activeContracts,
            'renewal_required_raw' => $renewalRequired,
            'due_this_period_raw' => $dueThisPeriod,
            'overdue_services_raw' => $overdueServices,
            'completed_visits_period_raw' => $completedVisitsPeriod,
            'renewals_period_raw' => $renewalsPeriod,
            'frequency_rows' => $frequencyRows,
            'upcoming_rows' => $upcomingRows,
            'renewal_rows' => $renewalRows,
            'summary_cards' => [
                [
                    'label' => 'Contratos activos',
                    'value' => $this->formatNumber($activeContracts),
                    'note' => $this->formatNumber($dueThisPeriod) . ' servicios caen dentro del periodo.',
                    'tone' => 'success',
                ],
                [
                    'label' => 'Servicios vencidos',
                    'value' => $this->formatNumber($overdueServices),
                    'note' => 'Conservaciones activas ya pasadas de fecha.',
                    'tone' => $overdueServices > 0 ? 'danger' : 'success',
                ],
                [
                    'label' => 'Renovacion requerida',
                    'value' => $this->formatNumber($renewalRequired),
                    'note' => $this->formatNumber($renewalsPeriod) . ' contratos renovados en el periodo.',
                    'tone' => $renewalRequired > 0 ? 'warning' : 'success',
                ],
                [
                    'label' => 'Visitas realizadas',
                    'value' => $this->formatNumber($completedVisitsPeriod),
                    'note' => 'Servicios de conservacion ya ejecutados en el rango filtrado.',
                    'tone' => 'emerald',
                ],
            ],
        ];
    }

    protected function buildBillingSnapshot(Carbon $from, Carbon $until): array
    {
        $records = BillingControl::query()
            ->with('client:id,name')
            ->select([
                'id',
                'client_id',
                'service_description',
                'amount',
                'invoice_number',
                'invoice_date',
                'invoiced',
                'paid',
                'payment_date',
                'created_at',
            ])
            ->get();

        $billedAmountPeriod = (float) $records
            ->filter(fn (BillingControl $record): bool => (bool) $record->invoiced && filled($record->invoice_date) && $record->invoice_date->between($from, $until))
            ->sum('amount');

        $paidAmountPeriod = (float) $records
            ->filter(fn (BillingControl $record): bool => (bool) $record->paid && filled($record->payment_date) && $record->payment_date->between($from, $until))
            ->sum('amount');

        $outstandingRecords = $records->filter(fn (BillingControl $record): bool => (bool) $record->invoiced && ! (bool) $record->paid);
        $outstandingAmount = (float) $outstandingRecords->sum('amount');
        $pendingInvoices = $outstandingRecords->count();
        $uninvoiced = $records->where('invoiced', false)->count();

        $overdueReceivables = $outstandingRecords->filter(function (BillingControl $record): bool {
            return filled($record->invoice_date) && $record->invoice_date->lt(today()->subDays(30));
        });

        $overdueAmount = (float) $overdueReceivables->sum('amount');
        $collectionRate = $billedAmountPeriod > 0 ? round(($paidAmountPeriod / $billedAmountPeriod) * 100, 1) : 0.0;

        $receivableRows = $outstandingRecords
            ->sortBy(function (BillingControl $record): int {
                return filled($record->invoice_date)
                    ? $record->invoice_date->getTimestamp()
                    : PHP_INT_MAX;
            })
            ->take(8)
            ->map(function (BillingControl $record): array {
                $invoiceDate = $record->invoice_date;
                $age = $invoiceDate ? $invoiceDate->diffInDays(today()) : null;

                return [
                    'client' => $record->client?->name ?? 'Sin cliente',
                    'description' => $record->service_description ?: 'Sin descripcion',
                    'invoice' => $record->invoice_number ?: 'Sin numero',
                    'invoice_date' => $invoiceDate?->format('d/m/Y') ?? 'Sin fecha',
                    'amount' => $this->formatMoney((float) $record->amount),
                    'age' => $age !== null ? $age . ' dias' : 'Sin edad',
                    'tone' => $invoiceDate && $invoiceDate->lt(today()->subDays(30)) ? 'danger' : 'warning',
                ];
            })
            ->values()
            ->all();

        return [
            'billed_amount_period_raw' => $billedAmountPeriod,
            'paid_amount_period_raw' => $paidAmountPeriod,
            'outstanding_amount_raw' => $outstandingAmount,
            'pending_invoices_raw' => $pendingInvoices,
            'uninvoiced_raw' => $uninvoiced,
            'overdue_amount_raw' => $overdueAmount,
            'collection_rate_raw' => $collectionRate,
            'receivable_rows' => $receivableRows,
            'summary_cards' => [
                [
                    'label' => 'Facturado en el periodo',
                    'value' => $this->formatMoney($billedAmountPeriod),
                    'note' => $this->formatMoney($paidAmountPeriod) . ' ya cobrados en el mismo rango.',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Pendiente de cobro',
                    'value' => $this->formatMoney($outstandingAmount),
                    'note' => $this->formatNumber($pendingInvoices) . ' facturas abiertas.',
                    'tone' => $outstandingAmount > 0 ? 'warning' : 'success',
                ],
                [
                    'label' => 'Mora de +30 dias',
                    'value' => $this->formatMoney($overdueAmount),
                    'note' => $this->formatPercent($collectionRate) . ' de recupero del periodo.',
                    'tone' => $overdueAmount > 0 ? 'danger' : 'success',
                ],
                [
                    'label' => 'Sin facturar',
                    'value' => $this->formatNumber($uninvoiced),
                    'note' => 'Servicios cargados todavia sin factura emitida.',
                    'tone' => $uninvoiced > 0 ? 'warning' : 'success',
                ],
            ],
        ];
    }

    protected function buildInventorySnapshot(Carbon $from, Carbon $until): array
    {
        $variants = ProductoVariante::query()
            ->with('producto')
            ->get();

        if (Schema::hasTable('stocks')) {
            $stockByVariant = DB::table('stocks')
                ->selectRaw('producto_variante_id, SUM(cantidad) as quantity')
                ->groupBy('producto_variante_id')
                ->pluck('quantity', 'producto_variante_id');
        } elseif (Schema::hasTable('stock')) {
            $stockByVariant = DB::table('stock')
                ->selectRaw('producto_variante_id, SUM(cantidad) as quantity')
                ->groupBy('producto_variante_id')
                ->pluck('quantity', 'producto_variante_id');
        } elseif (Schema::hasTable('producto_unidades')) {
            $stockByVariant = DB::table('producto_unidades')
                ->selectRaw("producto_variante_id, SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as quantity")
                ->groupBy('producto_variante_id')
                ->pluck('quantity', 'producto_variante_id');
        } else {
            $stockByVariant = collect();
        }

        $totalVariants = $variants->count();
        $totalStockUnits = (int) $stockByVariant->sum(fn ($quantity): int => (int) $quantity);

        $variantStockRows = $variants
            ->map(function (ProductoVariante $variant) use ($stockByVariant): array {
                return [
                    'variant' => $variant,
                    'quantity_raw' => (int) ($stockByVariant[$variant->id] ?? 0),
                ];
            })
            ->values();

        $criticalStock = $variantStockRows->filter(fn (array $row): bool => $row['quantity_raw'] <= 1)->count();
        $lowStock = $variantStockRows->filter(fn (array $row): bool => $row['quantity_raw'] > 1 && $row['quantity_raw'] <= 3)->count();
        $zeroStock = $variantStockRows->filter(fn (array $row): bool => $row['quantity_raw'] <= 0)->count();

        $serialMovements = Schema::hasTable('producto_unidad_movements')
            ? DB::table('producto_unidad_movements')
                ->whereBetween('created_at', [$from, $until])
                ->count()
            : 0;

        $sparePartMovements = Schema::hasTable('movimientos_repuestos')
            ? DB::table('movimientos_repuestos')
                ->whereBetween('created_at', [$from, $until])
                ->count()
            : 0;

        $movementsPeriod = $serialMovements + $sparePartMovements;

        $criticalRows = $variantStockRows
            ->filter(fn (array $row): bool => $row['quantity_raw'] <= 3)
            ->sortBy('quantity_raw')
            ->take(8)
            ->map(function (array $row): array {
                /** @var ProductoVariante $variant */
                $variant = $row['variant'];
                $quantity = $row['quantity_raw'];

                return [
                    'name' => $variant->display_name ?: 'Variante #' . $variant->id,
                    'code' => $variant->codigo_barra ?: 'Sin codigo',
                    'quantity' => $this->formatNumber($quantity),
                    'tone' => $quantity <= 1 ? 'danger' : 'warning',
                ];
            })
            ->values()
            ->all();

        return [
            'total_variants_raw' => $totalVariants,
            'total_stock_units_raw' => $totalStockUnits,
            'critical_stock_raw' => $criticalStock,
            'low_stock_raw' => $lowStock,
            'zero_stock_raw' => $zeroStock,
            'movements_period_raw' => $movementsPeriod,
            'critical_rows' => $criticalRows,
            'summary_cards' => [
                [
                    'label' => 'Variantes registradas',
                    'value' => $this->formatNumber($totalVariants),
                    'note' => $this->formatNumber($totalStockUnits) . ' unidades totales en stock.',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Stock critico',
                    'value' => $this->formatNumber($criticalStock),
                    'note' => 'Items con 1 unidad o menos.',
                    'tone' => $criticalStock > 0 ? 'danger' : 'success',
                ],
                [
                    'label' => 'Stock bajo',
                    'value' => $this->formatNumber($lowStock),
                    'note' => 'Items entre 2 y 3 unidades.',
                    'tone' => $lowStock > 0 ? 'warning' : 'success',
                ],
                [
                    'label' => 'Movimientos del periodo',
                    'value' => $this->formatNumber($movementsPeriod),
                    'note' => $this->formatNumber($zeroStock) . ' variantes sin stock disponible o sin registro.',
                    'tone' => 'info',
                ],
            ],
        ];
    }

    protected function buildHabilitationsSnapshot(Carbon $from, Carbon $until): array
    {
        $records = Habilitation::query()
            ->with('client:id,name')
            ->select([
                'id',
                'client_id',
                'equipment',
                'status',
                'doc_completa',
                'fecha_envio_gestor',
                'fecha_presentacion',
                'proxima_gestion',
                'updated_at',
            ])
            ->get();

        $openStatuses = ['pendiente', 'documentacion', 'enviado_gestor', 'en_tramite'];

        $openHabilitations = $records->whereIn('status', $openStatuses)->count();
        $docsPending = $records->filter(function (Habilitation $record): bool {
            return $record->status === 'documentacion' || ! $record->doc_completa;
        })->count();
        $approvedPeriod = $records->filter(function (Habilitation $record) use ($from, $until): bool {
            return $record->status === 'aprobado'
                && $record->updated_at?->between($from, $until);
        })->count();
        $overdueFollowUps = $records->filter(function (Habilitation $record) use ($openStatuses): bool {
            return in_array((string) $record->status, $openStatuses, true)
                && filled($record->proxima_gestion)
                && $record->proxima_gestion->lt(today());
        })->count();

        $statusRows = collect([
            'pendiente' => 'Pendiente',
            'documentacion' => 'Documentacion',
            'enviado_gestor' => 'Enviado gestor',
            'en_tramite' => 'En tramite',
            'aprobado' => 'Aprobado',
            'rechazado' => 'Rechazado',
            'archivado' => 'Archivado',
        ])->map(function (string $label, string $status) use ($records): array {
            $count = $records->where('status', $status)->count();

            return [
                'label' => $label,
                'count' => $this->formatNumber($count),
                'width' => max($count * 12, $count > 0 ? 18 : 0),
                'count_raw' => $count,
            ];
        })->filter(fn (array $row): bool => $row['count_raw'] > 0)->values()->map(function (array $row): array {
            unset($row['count_raw']);

            return $row;
        })->all();

        $queueRows = $records
            ->filter(fn (Habilitation $record): bool => in_array((string) $record->status, $openStatuses, true))
            ->sortBy(function (Habilitation $record): int {
                return filled($record->proxima_gestion)
                    ? $record->proxima_gestion->getTimestamp()
                    : PHP_INT_MAX;
            })
            ->take(6)
            ->map(function (Habilitation $record): array {
                return [
                    'client' => $record->client?->name ?? 'Sin cliente',
                    'equipment' => $record->equipment ?: 'Sin equipo',
                    'status' => (string) str($record->status)->replace('_', ' ')->title(),
                    'next_step' => $record->proxima_gestion?->format('d/m/Y') ?? 'Sin proxima gestion',
                    'tone' => $record->proxima_gestion && $record->proxima_gestion->lt(today()) ? 'danger' : 'warning',
                ];
            })
            ->values()
            ->all();

        return [
            'open_habilitations_raw' => $openHabilitations,
            'docs_pending_raw' => $docsPending,
            'approved_period_raw' => $approvedPeriod,
            'overdue_followups_raw' => $overdueFollowUps,
            'status_rows' => $statusRows,
            'queue_rows' => $queueRows,
            'summary_cards' => [
                [
                    'label' => 'Habilitaciones abiertas',
                    'value' => $this->formatNumber($openHabilitations),
                    'note' => 'Expedientes que siguen dentro del circuito.',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Documentacion incompleta',
                    'value' => $this->formatNumber($docsPending),
                    'note' => 'Casos trabados en papeles, firmas o apoderamientos.',
                    'tone' => $docsPending > 0 ? 'warning' : 'success',
                ],
                [
                    'label' => 'Gestiones vencidas',
                    'value' => $this->formatNumber($overdueFollowUps),
                    'note' => 'Proxima gestion ya pasada y sin movimiento.',
                    'tone' => $overdueFollowUps > 0 ? 'danger' : 'success',
                ],
                [
                    'label' => 'Aprobadas en el periodo',
                    'value' => $this->formatNumber($approvedPeriod),
                    'note' => 'Habilitaciones que avanzaron a cierre positivo.',
                    'tone' => 'emerald',
                ],
            ],
        ];
    }

    protected function buildOverviewCards(
        array $clients,
        array $commercial,
        array $operations,
        array $postSale,
        array $conservations,
        array $billing,
        array $inventory,
        array $habilitations,
    ): array {
        return [
            [
                'label' => 'Clientes registrados',
                'value' => $this->formatNumber($clients['total_clients_raw']),
                'note' => $this->formatNumber($clients['new_clients_raw']) . ' nuevos en el periodo.',
                'tone' => 'primary',
            ],
            [
                'label' => 'Leads nuevos',
                'value' => $this->formatNumber($commercial['new_leads_raw']),
                'note' => $this->formatNumber($commercial['open_pipeline_raw']) . ' oportunidades abiertas hoy.',
                'tone' => 'info',
            ],
            [
                'label' => 'Ventas cerradas',
                'value' => $this->formatNumber($commercial['sales_period_raw']),
                'note' => $this->formatMoney($commercial['revenue_period_raw']) . ' en el periodo.',
                'tone' => 'emerald',
            ],
            [
                'label' => 'Pendientes abiertos',
                'value' => $this->formatNumber($operations['open_pendings_raw']),
                'note' => $this->formatNumber($operations['overdue_pendings_raw']) . ' ya estan vencidos.',
                'tone' => $operations['overdue_pendings_raw'] > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Visitas tecnicas activas',
                'value' => $this->formatNumber($operations['active_visits_raw']),
                'note' => $this->formatNumber($operations['completed_visits_period_raw']) . ' finalizadas en el periodo.',
                'tone' => 'blue',
            ],
            [
                'label' => 'Reclamos abiertos',
                'value' => $this->formatNumber($postSale['open_claims_raw']),
                'note' => $this->formatNumber($postSale['overdue_claims_raw']) . ' con fecha atrasada.',
                'tone' => $postSale['overdue_claims_raw'] > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Conservaciones activas',
                'value' => $this->formatNumber($conservations['active_contracts_raw']),
                'note' => $this->formatNumber($conservations['renewal_required_raw']) . ' listas para renovar.',
                'tone' => 'success',
            ],
            [
                'label' => 'Cobranza pendiente',
                'value' => $this->formatMoney($billing['outstanding_amount_raw']),
                'note' => $this->formatNumber($billing['pending_invoices_raw']) . ' facturas abiertas.',
                'tone' => $billing['outstanding_amount_raw'] > 0 ? 'amber' : 'success',
            ],
            [
                'label' => 'Stock critico',
                'value' => $this->formatNumber($inventory['critical_stock_raw']),
                'note' => $this->formatNumber($inventory['zero_stock_raw']) . ' sin stock disponible.',
                'tone' => $inventory['critical_stock_raw'] > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Habilitaciones abiertas',
                'value' => $this->formatNumber($habilitations['open_habilitations_raw']),
                'note' => $this->formatNumber($habilitations['overdue_followups_raw']) . ' con seguimiento vencido.',
                'tone' => $habilitations['overdue_followups_raw'] > 0 ? 'amber' : 'info',
            ],
        ];
    }

    protected function buildCompanyPulse(
        array $commercial,
        array $operations,
        array $postSale,
        array $conservations,
        array $billing,
        array $inventory,
        array $habilitations,
    ): array {
        return [
            $this->makePulseCard(
                label: 'Comercial',
                value: $this->formatNumber($commercial['open_pipeline_raw']) . ' abiertos',
                note: $this->formatNumber($commercial['stale_open_leads_raw']) . ' sin ritmo y ' . $this->formatMoney($commercial['revenue_period_raw']) . ' vendidos en el periodo.',
                pressure: $commercial['stale_open_leads_raw'],
                warningAt: 5,
                dangerAt: 10,
            ),
            $this->makePulseCard(
                label: 'Operacion',
                value: $this->formatNumber($operations['overdue_pendings_raw']) . ' vencidos',
                note: $this->formatNumber($operations['open_pendings_raw']) . ' pendientes abiertos y ' . $this->formatNumber($operations['active_visits_raw']) . ' visitas activas.',
                pressure: $operations['overdue_pendings_raw'],
                warningAt: 3,
                dangerAt: 7,
            ),
            $this->makePulseCard(
                label: 'Postventa',
                value: $this->formatNumber($postSale['open_claims_raw']) . ' reclamos abiertos',
                note: $this->formatNumber($postSale['resolved_claims_period_raw']) . ' resueltos en el periodo.',
                pressure: $postSale['overdue_claims_raw'] + max($postSale['open_claims_raw'] - 6, 0),
                warningAt: 2,
                dangerAt: 5,
            ),
            $this->makePulseCard(
                label: 'Conservaciones',
                value: $this->formatNumber($conservations['renewal_required_raw']) . ' a renovar',
                note: $this->formatNumber($conservations['overdue_services_raw']) . ' servicios vencidos y ' . $this->formatNumber($conservations['completed_visits_period_raw']) . ' realizados.',
                pressure: $conservations['renewal_required_raw'] + $conservations['overdue_services_raw'],
                warningAt: 2,
                dangerAt: 5,
            ),
            $this->makePulseCard(
                label: 'Cobranzas',
                value: $this->formatMoney($billing['overdue_amount_raw']) . ' en mora',
                note: $this->formatMoney($billing['outstanding_amount_raw']) . ' pendientes totales y ' . $this->formatPercent($billing['collection_rate_raw']) . ' de recupero del periodo.',
                pressure: $billing['overdue_amount_raw'],
                warningAt: 200000,
                dangerAt: 600000,
            ),
            $this->makePulseCard(
                label: 'Inventario',
                value: $this->formatNumber($inventory['critical_stock_raw']) . ' criticos',
                note: $this->formatNumber($inventory['zero_stock_raw']) . ' sin stock y ' . $this->formatNumber($inventory['movements_period_raw']) . ' movimientos recientes.',
                pressure: $inventory['critical_stock_raw'] + $inventory['zero_stock_raw'],
                warningAt: 3,
                dangerAt: 7,
            ),
            $this->makePulseCard(
                label: 'Habilitaciones',
                value: $this->formatNumber($habilitations['docs_pending_raw']) . ' en documentacion',
                note: $this->formatNumber($habilitations['overdue_followups_raw']) . ' gestiones vencidas y ' . $this->formatNumber($habilitations['approved_period_raw']) . ' aprobadas en el periodo.',
                pressure: $habilitations['docs_pending_raw'] + $habilitations['overdue_followups_raw'],
                warningAt: 2,
                dangerAt: 5,
            ),
        ];
    }

    protected function buildAlerts(
        array $commercial,
        array $operations,
        array $postSale,
        array $conservations,
        array $billing,
        array $inventory,
        array $habilitations,
    ): array {
        $alerts = [
            [
                'title' => 'Pendientes vencidos',
                'value' => $this->formatNumber($operations['overdue_pendings_raw']),
                'detail' => 'Ordenes operativas abiertas cuya fecha ya paso.',
                'tone' => $operations['overdue_pendings_raw'] > 0 ? 'danger' : 'success',
                'severity' => $operations['overdue_pendings_raw'] * 10,
            ],
            [
                'title' => 'Leads sin seguimiento',
                'value' => $this->formatNumber($commercial['stale_open_leads_raw']),
                'detail' => 'Cartera abierta con mas de 3 dias sin gestion visible.',
                'tone' => $commercial['stale_open_leads_raw'] > 0 ? 'warning' : 'success',
                'severity' => $commercial['stale_open_leads_raw'] * 8,
            ],
            [
                'title' => 'Cobranza en mora',
                'value' => $this->formatMoney($billing['overdue_amount_raw']),
                'detail' => 'Facturas con mas de 30 dias y sin cobro.',
                'tone' => $billing['overdue_amount_raw'] > 0 ? 'danger' : 'success',
                'severity' => (int) round($billing['overdue_amount_raw'] / 50000),
            ],
            [
                'title' => 'Contratos para renovar',
                'value' => $this->formatNumber($conservations['renewal_required_raw']),
                'detail' => 'Conservaciones que ya agotaron ciclo o quedaron vencidas.',
                'tone' => $conservations['renewal_required_raw'] > 0 ? 'warning' : 'success',
                'severity' => $conservations['renewal_required_raw'] * 7,
            ],
            [
                'title' => 'Stock critico o sin registro',
                'value' => $this->formatNumber($inventory['critical_stock_raw'] + $inventory['zero_stock_raw']),
                'detail' => 'Variantes comprometidas para venta, mantenimiento o despacho.',
                'tone' => ($inventory['critical_stock_raw'] + $inventory['zero_stock_raw']) > 0 ? 'warning' : 'success',
                'severity' => ($inventory['critical_stock_raw'] + $inventory['zero_stock_raw']) * 6,
            ],
            [
                'title' => 'Gestiones de habilitacion vencidas',
                'value' => $this->formatNumber($habilitations['overdue_followups_raw']),
                'detail' => 'Casos administrativos sin movimiento y con fecha superada.',
                'tone' => $habilitations['overdue_followups_raw'] > 0 ? 'warning' : 'success',
                'severity' => $habilitations['overdue_followups_raw'] * 6,
            ],
        ];

        return collect($alerts)
            ->sortByDesc('severity')
            ->take(6)
            ->map(function (array $alert): array {
                unset($alert['severity']);

                return $alert;
            })
            ->values()
            ->all();
    }

    protected function buildInsights(
        array $commercial,
        array $operations,
        array $postSale,
        array $conservations,
        array $billing,
        array $inventory,
        array $habilitations,
        array $clients,
    ): array {
        $insights = [];

        $topSeller = collect($commercial['team_performance'])->first();
        if ($topSeller) {
            $insights[] = [
                'title' => 'Mejor traccion comercial del periodo',
                'description' => $topSeller['name'] . ' lidera con ' . $topSeller['won'] . ' cierres y ' . $topSeller['revenue'] . ' vendidos.',
                'tone' => 'success',
            ];
        }

        $busiestPendingType = collect($operations['pending_type_rows'])->first();
        if ($busiestPendingType) {
            $insights[] = [
                'title' => 'Frente operativo mas cargado',
                'description' => $busiestPendingType['label'] . ' concentra ' . $busiestPendingType['open'] . ' abiertos y ' . $busiestPendingType['in_progress'] . ' en proceso.',
                'tone' => 'primary',
            ];
        }

        if ($billing['outstanding_amount_raw'] > 0) {
            $insights[] = [
                'title' => 'Plata inmovilizada en cobranza',
                'description' => 'Hay ' . $this->formatMoney($billing['outstanding_amount_raw']) . ' pendientes de cobro; ' . $this->formatMoney($billing['overdue_amount_raw']) . ' ya entraron en mora.',
                'tone' => $billing['overdue_amount_raw'] > 0 ? 'danger' : 'warning',
            ];
        }

        if ($conservations['renewal_required_raw'] > 0) {
            $insights[] = [
                'title' => 'Renovaciones listas para accionar',
                'description' => $this->formatNumber($conservations['renewal_required_raw']) . ' contratos de conservacion ya necesitan renovacion y pueden transformarse en facturacion recurrente.',
                'tone' => 'warning',
            ];
        }

        if ($clients['new_clients_raw'] > 0) {
            $insights[] = [
                'title' => 'Crecimiento de base activa',
                'description' => $this->formatNumber($clients['new_clients_raw']) . ' clientes nuevos ingresaron en el periodo; conviene asegurar onboarding, postventa y documentacion de esos casos.',
                'tone' => 'neutral',
            ];
        }

        return array_slice($insights, 0, 4);
    }

    protected function makePulseCard(
        string $label,
        string $value,
        string $note,
        int|float $pressure,
        int|float $warningAt,
        int|float $dangerAt,
    ): array {
        $tone = match (true) {
            $pressure >= $dangerAt => 'danger',
            $pressure >= $warningAt => 'warning',
            default => 'success',
        };

        $score = match ($tone) {
            'danger' => 24,
            'warning' => 56,
            default => 86,
        };

        return [
            'label' => $label,
            'value' => $value,
            'note' => $note,
            'tone' => $tone,
            'status_label' => match ($tone) {
                'danger' => 'Critico',
                'warning' => 'Atencion',
                default => 'Estable',
            },
            'width' => $score,
        ];
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
}
