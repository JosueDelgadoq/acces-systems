<?php

namespace App\Filament\Resources\InventoryCategories\Pages;

use App\Filament\Resources\InventoryCategories\InventoryCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryCategory extends CreateRecord
{
    protected static string $resource = InventoryCategoryResource::class;
}
