<?php

namespace App\Filament\Resources\ProductoUnidads\Pages;

use App\Filament\Resources\ProductoUnidads\ProductoUnidadResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductoUnidads extends ListRecords
{
    protected static string $resource = ProductoUnidadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
