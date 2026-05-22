<?php

namespace App\Filament\Concerns;

trait HasPermissionControlledPage
{
    protected static function resolvePermission(): ?string
    {
        if (! property_exists(static::class, 'permission')) {
            return null;
        }

        return static::$permission;
    }

    public static function canAccess(): bool
    {
        $permission = static::resolvePermission();
        $user = auth()->user();

        if (blank($permission)) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $user->canAccess($permission);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
