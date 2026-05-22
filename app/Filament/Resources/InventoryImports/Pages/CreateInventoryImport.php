<?php

namespace App\Filament\Resources\InventoryImports\Pages;

use App\Filament\Resources\InventoryImports\InventoryImportResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryImport extends CreateRecord
{
    protected static string $resource = InventoryImportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['file_disk'] = $data['file_disk'] ?? 'local';
        $data['uploaded_by_user_id'] = auth()->id();
        $data['file_name'] = basename((string) $data['file_path']);

        return $data;
    }
}
