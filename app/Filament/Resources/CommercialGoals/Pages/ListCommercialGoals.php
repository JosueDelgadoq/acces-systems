<?php

namespace App\Filament\Resources\CommercialGoals\Pages;

use App\Filament\Resources\CommercialGoals\CommercialGoalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommercialGoals extends ListRecords
{
    protected static string $resource = CommercialGoalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
