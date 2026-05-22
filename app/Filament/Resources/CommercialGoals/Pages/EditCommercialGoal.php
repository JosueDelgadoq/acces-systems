<?php

namespace App\Filament\Resources\CommercialGoals\Pages;

use App\Filament\Resources\CommercialGoals\CommercialGoalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCommercialGoal extends EditRecord
{
    protected static string $resource = CommercialGoalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
