<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Single place that records the activity feed. Actor is the authenticated user.
 */
class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public static function log(string $type, Model $subject, ?int $projectId, array $meta = []): Activity
    {
        return Activity::create([
            'user_id' => Auth::id(),
            'subject_type' => class_basename($subject),
            'subject_id' => $subject->getKey(),
            'project_id' => $projectId,
            'type' => $type,
            'meta' => $meta,
        ]);
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            'todo' => 'To Do',
            'in_progress' => 'In Progress',
            'done' => 'Done',
            default => (string) $status,
        };
    }

    /**
     * Human readable sentence for an activity row.
     */
    public static function message(Activity $a): string
    {
        $who = $a->user?->name ?? 'Someone';
        $m = $a->meta ?? [];
        $t = $m['title'] ?? 'a task';

        return match ($a->type) {
            'task.created' => "$who created '$t'",
            'task.status_changed' => "$who moved '$t' from ".self::statusLabel($m['from'] ?? null).' to '.self::statusLabel($m['to'] ?? null),
            'task.assigned' => "$who assigned '$t' to ".($m['assignee'] ?? 'someone'),
            'task.due_changed' => "$who changed the due date of '$t' to ".($m['to'] ?? 'none'),
            'task.priority_changed' => "$who changed the priority of '$t' to ".($m['to'] ?? ''),
            'task.deleted' => "$who deleted '$t'",
            'task.commented' => "$who commented on '$t'",
            'member.added' => "$who added ".($m['member'] ?? 'a member').' to the project',
            'member.removed' => "$who removed ".($m['member'] ?? 'a member').' from the project',
            'member.role_changed' => "$who made ".($m['member'] ?? 'a member').' a project '.($m['role'] ?? 'member'),
            'project.created' => "$who created the project '".($m['name'] ?? '')."'",
            'project.updated' => "$who updated the project '".($m['name'] ?? '')."'",
            'project.archived' => "$who archived the project",
            'project.unarchived' => "$who restored the project",
            default => "$who did something",
        };
    }
}
