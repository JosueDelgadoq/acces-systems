<?php

namespace App\Support\Modules;

class LeadPermissions
{
    public const VIEW = 'lead.view';
    public const VIEW_ALL = 'lead.view_all';
    public const CREATE = 'lead.create';
    public const UPDATE = 'lead.update';
    public const DELETE = 'lead.delete';
    public const ASSIGN = 'lead.assign';
    public const CHANGE_STATUS = 'lead.change_status';

    protected static function user()
    {
        return auth()->user();
    }

    protected static function check(string $permission): bool
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

    public static function assign(): bool
    {
        return self::check(self::ASSIGN);
    }

    public static function changeStatus(): bool
    {
        return self::check(self::CHANGE_STATUS);
    }

    public static function all(): array
    {
        return [
            self::VIEW,
            self::VIEW_ALL,
            self::CREATE,
            self::UPDATE,
            self::DELETE,
            self::ASSIGN,
            self::CHANGE_STATUS,
        ];
    }

    public static function options(): array
    {
        return [
            self::VIEW => 'Ver leads',
            self::VIEW_ALL => 'Ver todos los leads',
            self::CREATE => 'Crear leads',
            self::UPDATE => 'Editar leads',
            self::DELETE => 'Eliminar leads',
            self::ASSIGN => 'Asignar leads',
            self::CHANGE_STATUS => 'Cambiar estado del pipeline',
        ];
    }
}
