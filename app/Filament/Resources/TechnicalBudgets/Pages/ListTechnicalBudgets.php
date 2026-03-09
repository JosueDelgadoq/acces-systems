<?php

namespace App\Filament\Resources\TechnicalBudgets\Pages;

use App\Filament\Resources\TechnicalBudgets\TechnicalBudgetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTechnicalBudgets extends ListRecords
{
    protected static string $resource = TechnicalBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

