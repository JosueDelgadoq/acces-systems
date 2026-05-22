<?php

namespace App\Filament\Resources\Repuestos\Pages;

use App\Filament\Resources\Repuestos\RepuestoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRepuestos extends ListRecords
{
    protected static string $resource = RepuestoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
