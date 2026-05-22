<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Support\PermissionCatalog;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected array $selectedPermissions = [];

    protected function afterCreate(): void
    {
        $this->record->syncPermissions($this->selectedPermissions);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->selectedPermissions = PermissionCatalog::ensurePermissionsExist(
            PermissionCatalog::extractSelectedPermissions($this->data),
            config('auth.defaults.guard', 'web'),
        );

        return collect($data)
            ->reject(fn (mixed $value, string $key): bool => str_starts_with($key, 'permissions_') || $key === 'permissions')
            ->toArray();
    }
}
