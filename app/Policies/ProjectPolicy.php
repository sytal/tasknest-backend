<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
    /**
     * Director and CEO bypass every check.
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isManagement() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): Response
    {
        return $project->hasMember($user->id) ? Response::allow() : Response::denyAsNotFound();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Project $project): bool
    {
        return false;
    }

    public function delete(User $user, Project $project): bool
    {
        return false;
    }

    public function manageMembers(User $user, Project $project): bool
    {
        return false;
    }

    /** Archive/unarchive and label management: leads (management bypasses). */
    public function lead(User $user, Project $project): bool
    {
        return $project->isLead($user->id);
    }
}
