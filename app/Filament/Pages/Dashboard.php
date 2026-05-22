<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionControlledPage;
use App\Filament\Resources\Claims\ClaimResource;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Pendientes\PendienteResource;
use App\Filament\Widgets\AlertsWidget;
use App\Filament\Widgets\LostReasonsChart;
use App\Filament\Widgets\PendientesStats;
use App\Filament\Widgets\PipelineWidget;
use App\Filament\Widgets\SalesVsLostChart;
use App\Filament\Widgets\SectionHeader;
use App\Filament\Widgets\SeguimientosHoy;
use App\Filament\Widgets\StatsOverview;
use App\Models\Claim;
use App\Models\Lead;
use App\Models\Pendiente;
use App\Models\Seguimiento;
use App\Models\Venta;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Carbon;

class Dashboard extends BaseDashboard
{
    use HasPermissionControlledPage;

    protected static ?string $permission = 'dashboard.view';

    protected string $view = 'filament.pages.dashboard';

    public array $heroStats = [];

    public array $quickActions = [];

    public array $focusAreas = [];

    public array $operationalItems = [];

    public function mount(): void
    {
        $today = now();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        $totalPendientes = Pendiente::query()
            ->where('status', '!=', Pendiente::STATUS_COMPLETED)
            ->count();

        $seguimientosHoy = Seguimiento::query()
            ->where('estado', 'pendiente')
            ->whereDate('fecha_proxima_accion', $today)
            ->count();

        $ventasMes = Venta::query()
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();

        $this->heroStats = [
            ['label' => 'Pendientes activos', 'value' => number_format($totalPendientes, 0, ',', '.')],
            ['label' => 'Agenda de hoy', 'value' => number_format($seguimientosHoy, 0, ',', '.')],
            ['label' => 'Ventas del mes', 'value' => number_format($ventasMes, 0, ',', '.')],
        ];

        $this->quickActions = [
            [
                'title' => 'Revisar pendientes',
                'description' => 'Entrar a la cola operativa y reasignar trabajo critico.',
                'url' => PendienteResource::getUrl(),
            ],
            [
                'title' => 'Abrir pipeline',
                'description' => 'Seguir oportunidades abiertas y mover el frente comercial.',
                'url' => PipelineLeads::getUrl(),
            ],
            [
                'title' => 'Metricas comerciales',
                'description' => 'Medir conversion, rendimiento y motivos de perdida por responsable y canal.',
                'url' => CommercialMetrics::getUrl(),
            ],
            [
                'title' => 'Ver calendario',
                'description' => 'Controlar vencimientos, visitas y compromisos del dia.',
                'url' => CalendarioPendientes::getUrl(),
            ],
            [
                'title' => 'Consultar clientes',
                'description' => 'Acceder rapido a cartera, historial y reclamos asociados.',
                'url' => ClientResource::getUrl(),
            ],
        ];

        $activeLeads = Lead::query()
            ->whereNotIn('estado_pipeline', ['Venta cerrada', 'Perdido'])
            ->count();

        $overduePendings = Pendiente::query()
            ->where('status', '!=', Pendiente::STATUS_COMPLETED)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $today)
            ->count();

        $highPriorityPendings = Pendiente::query()
            ->where('status', '!=', Pendiente::STATUS_COMPLETED)
            ->where('priority', 'alta')
            ->count();

        $claimsCount = Claim::query()->count();

        $this->focusAreas = [
            [
                'title' => 'Operacion',
                'description' => 'Pendientes activos, urgencias y trabajo asignable.',
                'metric' => $totalPendientes . ' tareas en curso',
                'url' => PendienteResource::getUrl(),
            ],
            [
                'title' => 'Comercial',
                'description' => 'Seguimiento de leads con oportunidad vigente.',
                'metric' => $activeLeads . ' oportunidades abiertas',
                'url' => LeadResource::getUrl(),
            ],
            [
                'title' => 'Clientes y reclamos',
                'description' => 'Consulta de cuentas, historial y demanda postventa.',
                'metric' => $claimsCount . ' reclamos registrados',
                'url' => ClaimResource::getUrl(),
            ],
        ];

        $this->operationalItems = [
            [
                'title' => 'Pendientes vencidos',
                'detail' => 'Casos que ya superaron su fecha compromiso.',
                'value' => $overduePendings,
                'tone' => $overduePendings > 0 ? 'danger' : 'success',
            ],
            [
                'title' => 'Alta prioridad',
                'detail' => 'Tareas marcadas con criticidad alta.',
                'value' => $highPriorityPendings,
                'tone' => $highPriorityPendings > 0 ? 'warning' : 'success',
            ],
            [
                'title' => 'Seguimientos del dia',
                'detail' => 'Acciones comerciales programadas para la jornada.',
                'value' => $seguimientosHoy,
                'tone' => $seguimientosHoy > 0 ? 'warning' : 'success',
            ],
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
            AlertsWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }

    protected function getFooterWidgets(): array
    {
        return [
            SectionHeader::make(['title' => 'Rendimiento comercial']),
            SalesVsLostChart::class,
            LostReasonsChart::class,
            PipelineWidget::class,
            SectionHeader::make(['title' => 'Operacion diaria']),
            SeguimientosHoy::class,
            PendientesStats::class,
        ];
    }

    public function getFooterWidgetsColumns(): int | array
    {
        return 12;
    }

    public function getDashboardDate(): string
    {
        return Carbon::now()->translatedFormat('l, d \d\e F');
    }

    public function getDashboardKpis(): array
    {
        $today = now();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        return [
            [
                'label' => 'Leads nuevos',
                'value' => Lead::query()->whereDate('created_at', $today)->count(),
                'note' => 'ingresados durante hoy',
            ],
            [
                'label' => 'Leads activos',
                'value' => Lead::query()
                    ->whereNotIn('estado_pipeline', ['Venta cerrada', 'Perdido'])
                    ->count(),
                'note' => 'oportunidades en gestion',
            ],
            [
                'label' => 'Seguimientos',
                'value' => Seguimiento::query()->where('estado', 'pendiente')->count(),
                'note' => 'acciones pendientes',
            ],
            [
                'label' => 'Ventas mes',
                'value' => Venta::query()->whereBetween('created_at', [$monthStart, $monthEnd])->count(),
                'note' => 'cierres acumulados del mes',
            ],
        ];
    }
}
