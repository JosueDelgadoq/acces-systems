<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Charts\ClaimsChart;
use App\Filament\Widgets\Charts\ConservationsChart;
use App\Filament\Widgets\Charts\BillingChart;
use App\Filament\Widgets\Charts\MonthlyConservationsChart;
use BackedEnum;
use Filament\Pages\Page;

class Reports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Informes';

    protected static ?string $title = 'Informes y Gráficos';

    protected string $view = 'filament.pages.reports';

    protected function getWidgets(): array
    {
        return [
            MonthlyConservationsChart::class,
            ClaimsChart::class,
            BillingChart::class,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
}
