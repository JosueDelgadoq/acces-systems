<?php

namespace App\Filament\Resources\TechnicalBudgets\Pages;

use App\Filament\Resources\TechnicalBudgets\TechnicalBudgetResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTechnicalBudget extends EditRecord
{
    protected static string $resource = TechnicalBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

