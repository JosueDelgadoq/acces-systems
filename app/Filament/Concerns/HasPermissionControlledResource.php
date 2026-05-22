<?php

namespace App\Filament\Concerns;

trait HasPermissionControlledResource
{
    protected static function hasPermission(?string $permission): bool
    {
        return blank($permission) || (auth()->user()?->canAccess($permission) ?? false);
    }

    protected static function resolvePermissionProperty(string $property): ?string
    {
        if (! property_exists(static::class, $property)) {
            return null;
        }

        return static::${$property};
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::hasPermission(
            static::resolvePermissionProperty('navigationPermission')
            ?? static::resolvePermissionProperty('viewAnyPermission')
            ?? static::resolvePermissionProperty('createPermission')
            ?? static::resolvePermissionProperty('updatePermission')
        );
    }

    public static function canViewAny(): bool
    {
        return static::hasPermission(
            static::resolvePermissionProperty('viewAnyPermission')
            ?? static::resolvePermissionProperty('navigationPermission'),
        );
    }

    public static function canCreate(): bool
    {
        return static::hasPermission(static::resolvePermissionProperty('createPermission'));
    }

    public static function canEdit($record): bool
    {
        return static::hasPermission(static::resolvePermissionProperty('updatePermission'));
    }

    public static function canDelete($record): bool
    {
        return static::hasPermission(static::resolvePermissionProperty('deletePermission'));
    }
}
