<?php

namespace App\Support\Modules;

class InventarioPermissions
{
    public const VIEW = 'inventario.view';
    public const VIEW_ALL = 'inventario.view_all';
    public const CREATE = 'inventario.create';
    public const UPDATE = 'inventario.update';
    public const DELETE = 'inventario.delete';

    protected static function user()
    {
        return auth()->user();
    }

    protected static function check($permission): bool
    {
        return self::user()?->can($permission) ?? false;
    }

    public static function view(): bool
    {
        return self::check(self::VIEW);
    }

    public static function viewAll(): bool
    {
        return self::check(self::VIEW_ALL);
    }

    public static function create(): bool
    {
        return self::check(self::CREATE);
    }

    public static function update(): bool
    {
        return self::check(self::UPDATE);
    }

    public static function delete(): bool
    {
        return self::check(self::DELETE);
    }

    public static function all(): array
{
    return [
        self::VIEW,
        self::VIEW_ALL,
        self::CREATE,
        self::UPDATE,
        self::DELETE,
    ];
}

public static function options(): array
{
    return [
        self::VIEW => 'Ver inventario',
        self::VIEW_ALL => 'Ver todos',
        self::CREATE => 'Crear',
        self::UPDATE => 'Editar',
        self::DELETE => 'Eliminar',
    ];
}
}