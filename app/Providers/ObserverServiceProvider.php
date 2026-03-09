<?php

namespace App\Providers;

use App\Models\Habilitation;
use App\Models\TechnicalBudget;
use App\Models\EquipmentDelivery;
use App\Models\Claim;
use App\Observers\HabilitationObserver;
use App\Observers\TechnicalBudgetObserver;
use App\Observers\EquipmentDeliveryObserver;
use App\Observers\ClaimObserver;
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
    }
}

