<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CommentPolicy
{
    public function delete(User $user, Comment $comment): Response
    {
        $task = $comment->task;
        $project = $task->project;
        if (! $user->isManagement() && ! $project->hasMember($user->id)) {
            return Response::denyAsNotFound();
        }

        return $user->isManagement() || $comment->user_id === $user->id || $project->isLead($user->id)
            ? Response::allow()
            : Response::deny('You cannot delete this comment.');
    }
}
