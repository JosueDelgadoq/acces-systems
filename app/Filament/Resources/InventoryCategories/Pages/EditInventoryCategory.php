<?php

namespace App\Filament\Resources\InventoryCategories\Pages;

use App\Filament\Resources\InventoryCategories\InventoryCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInventoryCategory extends EditRecord
{
    protected static string $resource = InventoryCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
