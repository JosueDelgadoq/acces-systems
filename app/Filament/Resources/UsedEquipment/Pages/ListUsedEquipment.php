<?php

namespace App\Filament\Resources\UsedEquipment\Pages;

use App\Filament\Resources\UsedEquipment\UsedEquipmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsedEquipment extends ListRecords
{
    protected static string $resource = UsedEquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
