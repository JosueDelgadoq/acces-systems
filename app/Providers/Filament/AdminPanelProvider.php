<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Reports;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Claims\ClaimResource;
use App\Filament\Resources\Conservations\ConservationResource;
use App\Filament\Resources\Habilitations\HabilitationResource;
use App\Filament\Resources\TechnicalBudgets\TechnicalBudgetResource;
use App\Filament\Resources\EquipmentDeliveries\EquipmentDeliveryResource;
use App\Filament\Resources\BillingControls\BillingControlResource;
use App\Filament\Resources\PartsOrders\PartsOrderResource;
use App\Filament\Widgets\BloqueAStats;
use App\Filament\Widgets\BloqueBStats;
use App\Filament\Widgets\BloqueCStats;
use App\Filament\Widgets\Charts\ConservationsChart;
use App\Filament\Widgets\Charts\ClaimsChart;
use App\Filament\Widgets\Charts\BillingChart;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('ERP Postventa')
            ->colors([
                'primary' => Color::Violet,
                'secondary' => Color::Indigo,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
                'gray' => Color::Slate,
            ])
            ->navigationGroups([
                NavigationGroup::make('Gestión')
                    ->label('Gestión'),
                NavigationGroup::make('BLOQUE A - Admin')
                    ->label('BLOQUE A - Admin'),
                NavigationGroup::make('BLOQUE B - Operaciones')
                    ->label('BLOQUE B - Operaciones'),
                NavigationGroup::make('BLOQUE C - Control')
                    ->label('BLOQUE C - Control'),
                NavigationGroup::make('Comercial')
                    ->label('Comercial'),
            ])
            ->resources([
                UserResource::class,
                ClientResource::class,

                ClaimResource::class,
                ConservationResource::class,
                HabilitationResource::class,
                TechnicalBudgetResource::class,
                EquipmentDeliveryResource::class,
                BillingControlResource::class,
                PartsOrderResource::class,
                \App\Filament\Resources\Leads\LeadResource::class,
                \App\Filament\Resources\Pendientes\PendienteResource::class,
                \App\Filament\Resources\Presupuestos\PresupuestoResource::class,
\App\Filament\Resources\Ventas\VentaResource::class,

            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                Reports::class,
            ])
            ->widgets([
                \App\Filament\Widgets\SalesFunnel::class,
                AccountWidget::class,
                BloqueAStats::class,
                BloqueBStats::class,
                BloqueCStats::class,
                \App\Filament\Widgets\ClaimsStats::class,
                \App\Filament\Widgets\ConservationsStats::class,
                ConservationsChart::class,
                ClaimsChart::class,
                BillingChart::class,
                \App\Filament\Widgets\BloqueComercialStats::class,
                \App\Filament\Widgets\LeadsFunnelChart::class,
            ])

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->spa();
    }
}
