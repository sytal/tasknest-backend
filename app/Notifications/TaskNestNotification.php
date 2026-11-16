<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Generic in-app notification stored in the database channel. A push channel
 * (e.g. FCM) can be added later by extending via().
 */
class TaskNestNotification extends Notification
{
    public function __construct(
        public string $kind,
        public string $message,
        public ?int $taskId = null,
        public ?int $projectId = null,
        public ?string $dedupe = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'kind' => $this->kind,
            'message' => $this->message,
            'task_id' => $this->taskId,
            'project_id' => $this->projectId,
            'dedupe' => $this->dedupe,
        ];
    }
}
