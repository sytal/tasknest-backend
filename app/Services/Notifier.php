<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\TaskNestNotification;
use Illuminate\Support\Facades\Auth;

class Notifier
{
    /**
     * Notify a user, skipping the actor themselves and (optionally) duplicates.
     */
    public static function send(?User $to, string $kind, string $message, ?int $taskId = null, ?int $projectId = null, ?string $dedupe = null): void
    {
        if (! $to || $to->id === Auth::id() || ! $to->is_active) {
            return;
        }
        if ($dedupe && $to->notifications()->where('data->dedupe', $dedupe)->exists()) {
            return;
        }
        $to->notify(new TaskNestNotification($kind, $message, $taskId, $projectId, $dedupe));
    }

    /**
     * Same as send() but for scheduled jobs where there is no acting user.
     */
    public static function system(User $to, string $kind, string $message, int $taskId, int $projectId, string $dedupe): void
    {
        if (! $to->is_active || $to->notifications()->where('data->dedupe', $dedupe)->exists()) {
            return;
        }
        $to->notify(new TaskNestNotification($kind, $message, $taskId, $projectId, $dedupe));
    }
}
