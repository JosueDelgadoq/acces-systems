<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Venta;
use Illuminate\Auth\Access\HandlesAuthorization;

class VentaPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isTecnico();
    }

    public function view(User $user, Venta $venta): bool
    {
        return $user->isAdmin() || $user->isTecnico() || $venta->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTecnico();
    }

    public function update(User $user, Venta $venta): bool
    {
        return $user->isAdmin() || $user->isTecnico() || $venta->created_by === $user->id;
    }

    public function delete(User $user, Venta $venta): bool
    {
        return $user->isAdmin() || $venta->created_by === $user->id;
    }
}

