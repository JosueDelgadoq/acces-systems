<?php

namespace App\Filament\Resources\Ventas\Pages;

use App\Filament\Resources\Ventas\VentaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVenta extends EditRecord
{
    protected static string $resource = VentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
