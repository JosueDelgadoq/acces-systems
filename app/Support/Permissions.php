<?php

namespace App\Support;

class Permissions
{
    public static function can($permission): bool
    {
        return auth()->check() && auth()->user()->can($permission);
    }

    public static function isAdmin(): bool
    {
        return auth()->user()?->hasRole('admin');
    }
}