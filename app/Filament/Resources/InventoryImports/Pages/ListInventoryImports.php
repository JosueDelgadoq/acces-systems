<?php

namespace App\Filament\Resources\InventoryImports\Pages;

use App\Filament\Resources\InventoryImports\InventoryImportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInventoryImports extends ListRecords
{
    protected static string $resource = InventoryImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
