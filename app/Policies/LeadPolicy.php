<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeadPolicy
{
    use HandlesAuthorization;

public function viewAny(User $user): bool
{
    return $user->isAdmin() || $user->isTecnico();
}

public function view(User $user, Lead $lead): bool
{
    return $user->isAdmin()
        || $user->isTecnico()
        || $lead->created_by === $user->id;
}

public function create(User $user): bool
{
    return $user->isAdmin() || $user->isTecnico();
}
    public function update(User $user, Lead $lead): bool
    {
    return $user->isAdmin() || $user->isTecnico() || $lead->created_by === $user->id;
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->isAdmin() || $lead->created_by === $user->id;
    }
}

