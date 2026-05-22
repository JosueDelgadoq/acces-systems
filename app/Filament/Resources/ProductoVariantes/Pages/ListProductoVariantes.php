<?php

namespace App\Filament\Resources\ProductoVariantes\Pages;

use App\Filament\Resources\ProductoVariantes\ProductoVarianteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductoVariantes extends ListRecords
{
    protected static string $resource = ProductoVarianteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
