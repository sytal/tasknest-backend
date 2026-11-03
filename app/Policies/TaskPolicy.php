<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
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

    public function view(User $user, Task $task): bool
    {
        return $task->project->hasMember($user->id);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Task $task): bool
    {
        return false;
    }

    /**
     * A member may update the status of a task assigned to them.
     */
    public function updateStatus(User $user, Task $task): bool
    {
        return $task->assignee_id === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return false;
    }

    /**
     * Only director/ceo (via before()) may bulk-assign tasks within a project.
     */
    public function bulkAssign(User $user): bool
    {
        return false;
    }
}
