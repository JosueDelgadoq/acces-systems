<?php

namespace App\Filament\Resources\ProductoVariantes\Pages;

use App\Filament\Resources\ProductoVariantes\ProductoVarianteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductoVariante extends EditRecord
{
    protected static string $resource = ProductoVarianteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
