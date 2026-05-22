<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Support\Modules\LeadPermissions;
use Illuminate\Auth\Access\HandlesAuthorization;

class LeadPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->canAccess('lead.view');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->canAccess(LeadPermissions::VIEW)
            && (
                $user->canAccess(LeadPermissions::VIEW_ALL)
                || $user->canAccess(LeadPermissions::ASSIGN)
                || $lead->created_by === $user->id
                || $lead->comercial_asignado_id === $user->id
            );
    }

    public function create(User $user): bool
    {
        return $user->canAccess('lead.create');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->canAccess(LeadPermissions::UPDATE)
            && (
                $user->canAccess(LeadPermissions::VIEW_ALL)
                || $user->canAccess(LeadPermissions::ASSIGN)
                || $lead->created_by === $user->id
                || $lead->comercial_asignado_id === $user->id
            );
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->canAccess('lead.delete');
    }
}
