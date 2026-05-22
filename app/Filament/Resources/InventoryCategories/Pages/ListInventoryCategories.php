<?php

namespace App\Filament\Resources\InventoryCategories\Pages;

use App\Filament\Resources\InventoryCategories\InventoryCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInventoryCategories extends ListRecords
{
    protected static string $resource = InventoryCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
