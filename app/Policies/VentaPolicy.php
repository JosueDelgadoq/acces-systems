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
        return $user->can('venta.view');
    }

    public function view(User $user, Venta $venta): bool
    {
        return $user->can('venta.view')
            && ($venta->created_by === $user->id || $user->hasRole('gerente'));
    }

    public function create(User $user): bool
    {
        return $user->can('venta.create');
    }

    public function update(User $user, Venta $venta): bool
    {
        return $user->can('venta.update')
            && $venta->created_by === $user->id;
    }

    public function delete(User $user, Venta $venta): bool
    {
        return $user->can('venta.delete');
    }

    public function close(User $user, Venta $venta): bool
    {
        return $user->can('venta.close')
            && $venta->created_by === $user->id;
    }
}
