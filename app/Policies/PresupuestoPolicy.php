<?php

namespace App\Policies;

use App\Models\Presupuesto;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PresupuestoPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('presupuesto.view');
    }

    public function view(User $user, Presupuesto $presupuesto): bool
    {
        return $user->can('presupuesto.view')
            && ($presupuesto->created_by === $user->id || $user->hasRole('gerente'));
    }

    public function create(User $user): bool
    {
        return $user->can('presupuesto.create');
    }

    public function update(User $user, Presupuesto $presupuesto): bool
    {
        return $user->can('presupuesto.update')
            && $presupuesto->created_by === $user->id;
    }

    public function delete(User $user, Presupuesto $presupuesto): bool
    {
        return $user->can('presupuesto.delete');
    }

    public function send(User $user): bool
    {
        return $user->can('presupuesto.send');
    }
}
