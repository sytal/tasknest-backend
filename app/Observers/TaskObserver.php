<?php

namespace App\Observers;

use App\Models\Task;
use App\Services\ActivityLogger;
use App\Services\Notifier;

class TaskObserver
{
    public function created(Task $task): void
    {
        ActivityLogger::log('task.created', $task, $task->project_id, ['title' => $task->title]);
        $this->notifyAssigned($task);
    }

    public function updated(Task $task): void
    {
        $title = $task->title;
        if ($task->wasChanged('status')) {
            ActivityLogger::log('task.status_changed', $task, $task->project_id, [
                'title' => $title,
                'from' => $task->getOriginal('status'),
                'to' => $task->status,
            ]);
        }
        if ($task->wasChanged('assignee_id')) {
            $task->loadMissing('assignee');
            ActivityLogger::log('task.assigned', $task, $task->project_id, [
                'title' => $title,
                'assignee' => $task->assignee?->name,
            ]);
            $this->notifyAssigned($task);
        }
        if ($task->wasChanged('due_date')) {
            ActivityLogger::log('task.due_changed', $task, $task->project_id, [
                'title' => $title,
                'to' => $task->due_date?->toDateString(),
            ]);
        }
        if ($task->wasChanged('priority')) {
            ActivityLogger::log('task.priority_changed', $task, $task->project_id, [
                'title' => $title,
                'to' => $task->priority,
            ]);
        }
    }

    public function deleted(Task $task): void
    {
        ActivityLogger::log('task.deleted', $task, $task->project_id, ['title' => $task->title]);
    }

    private function notifyAssigned(Task $task): void
    {
        $task->loadMissing('assignee');
        Notifier::send($task->assignee, 'task_assigned', "You were assigned '{$task->title}'", $task->id, $task->project_id);
    }
}
