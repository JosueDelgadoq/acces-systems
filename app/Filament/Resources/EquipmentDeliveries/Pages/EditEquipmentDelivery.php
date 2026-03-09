<?php

namespace App\Filament\Resources\EquipmentDeliveries\Pages;

use App\Filament\Resources\EquipmentDeliveries\EquipmentDeliveryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEquipmentDelivery extends EditRecord
{
    protected static string $resource = EquipmentDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

