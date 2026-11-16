<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Services\Notifier;
use Illuminate\Console\Command;

class SendDueReminders extends Command
{
    protected $signature = 'tasks:send-due-reminders';

    protected $description = 'Notify assignees of tasks due tomorrow or overdue (once per task per day).';

    public function handle(): int
    {
        $today = today();
        $count = 0;

        Task::with(['assignee', 'project'])
            ->where('status', '!=', 'done')->whereNotNull('assignee_id')->whereNotNull('due_date')
            ->whereHas('project', fn ($p) => $p->where('status', 'active'))
            ->whereDate('due_date', '<=', $today->copy()->addDay())
            ->each(function (Task $task) use ($today, &$count) {
                $overdue = $task->due_date->lt($today);
                $due = $task->due_date->isSameDay($today->copy()->addDay());
                if (! $overdue && ! $due) {
                    return;
                }
                $kind = $overdue ? 'task_overdue' : 'task_due_tomorrow';
                $msg = $overdue ? "'{$task->title}' is overdue" : "'{$task->title}' is due tomorrow";
                Notifier::system($task->assignee, $kind, $msg, $task->id, $task->project_id, "$kind:{$task->id}:{$today->toDateString()}");
                $count++;
            });

        $this->info("Processed {$count} reminder(s).");

        return self::SUCCESS;
    }
}
