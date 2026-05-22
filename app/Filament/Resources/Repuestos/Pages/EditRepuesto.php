<?php

namespace App\Filament\Resources\Repuestos\Pages;

use App\Filament\Resources\Repuestos\RepuestoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRepuesto extends EditRecord
{
    protected static string $resource = RepuestoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
