<?php

namespace App\Filament\Resources\EquipmentDeliveries\Pages;

use App\Filament\Resources\EquipmentDeliveries\EquipmentDeliveryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEquipmentDeliveries extends ListRecords
{
    protected static string $resource = EquipmentDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

