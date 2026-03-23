<?php

namespace App\Filament\Widgets;

use App\Models\BillingControl;
use App\Models\PartsOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Support\Icons\Heroicon;

class BloqueCStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Facturación', BillingControl::count())
                ->description('Total controles')
                ->descriptionIcon(Heroicon::OutlinedDocumentText)
                ->color('secondary')
                ->chart([10, 15, 20, 25, 30, 35, 40]),

            Stat::make('Pendiente Cobro', BillingControl::where('paid', false)->count())
                ->description('Por cobrar')
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('danger'),

            Stat::make('Repuestos', PartsOrder::count())
                ->description('Pedidos abiertos')
                ->descriptionIcon(Heroicon::OutlinedCog)
                ->color('gray')
                ->chart([1, 2, 3, 2, 4, 3, 5]),
        ];
    }

    protected function getHeading(): ?string
    {
        return 'BLOQUE C - Control';
    }
}

