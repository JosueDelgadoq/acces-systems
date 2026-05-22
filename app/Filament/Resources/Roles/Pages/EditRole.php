<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Support\PermissionCatalog;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected array $selectedPermissions = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => $this->record->name !== 'admin'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->selectedPermissions = PermissionCatalog::ensurePermissionsExist(
            PermissionCatalog::extractSelectedPermissions($this->data),
            $this->record->guard_name,
        );

        return collect($data)
            ->reject(fn (mixed $value, string $key): bool => str_starts_with($key, 'permissions_') || $key === 'permissions')
            ->toArray();
    }

    protected function afterSave(): void
    {
        $this->record->syncPermissions($this->selectedPermissions);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return array_merge(
            $data,
            PermissionCatalog::expandPermissionsForForm(
                $this->record->permissions->pluck('name')->all(),
            ),
        );
    }
}
