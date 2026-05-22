<?php

namespace App\Providers;

use App\Models\Habilitation;
use App\Models\TechnicalBudget;
use App\Models\EquipmentDelivery;
use App\Models\Claim;
use App\Models\Lead;
use App\Models\Pendiente;
use App\Models\Seguimiento;
use App\Observers\HabilitationObserver;
use App\Observers\TechnicalBudgetObserver;
use App\Observers\EquipmentDeliveryObserver;
use App\Observers\ClaimObserver;
use App\Observers\LeadObserver;
use App\Observers\PendienteObserver;
use App\Observers\SeguimientoObserver;
use Illuminate\Support\ServiceProvider;

class ObserverServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Registrar observers
        Habilitation::observe(HabilitationObserver::class);
        TechnicalBudget::observe(TechnicalBudgetObserver::class);
        EquipmentDelivery::observe(EquipmentDeliveryObserver::class);
        Claim::observe(ClaimObserver::class);
        Lead::observe(LeadObserver::class);
        Pendiente::observe(PendienteObserver::class);
        Seguimiento::observe(SeguimientoObserver::class);
    }
}

