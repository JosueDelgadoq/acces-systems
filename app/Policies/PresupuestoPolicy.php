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
        return $user->isAdmin() || $user->isTecnico();
    }

    public function view(User $user, Presupuesto $presupuesto): bool
    {
        return $user->isAdmin() || $user->isTecnico() || $presupuesto->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isTecnico();
    }

    public function update(User $user, Presupuesto $presupuesto): bool
    {
        return $user->isAdmin() || $user->isTecnico() || $presupuesto->created_by === $user->id;
    }

    public function delete(User $user, Presupuesto $presupuesto): bool
    {
        return $user->isAdmin() || $presupuesto->created_by === $user->id;
    }
}

