<?php

/**
 * Seeded TaskNest login credentials (all passwords: "password"):
 *
 *  Director : director@tasknest.test
 *  CEO      : ceo@tasknest.test
 *  Members  : alice@tasknest.test   (Senior Developer)
 *             bob@tasknest.test     (QA Engineer)
 *             carol@tasknest.test   (UI/UX Designer)
 *             dave@tasknest.test    (Backend Developer)
 *             erin@tasknest.test    (DevOps Engineer)
 */

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Comment;
use App\Models\Label;
use App\Models\Project;
use App\Models\Subtask;
use App\Notifications\TaskNestNotification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        $director = User::create([
            'name' => 'Daniel Director',
            'email' => 'director@tasknest.test',
            'password' => $password,
            'role' => 'director',
            'designation' => 'Director',
        ]);

        $ceo = User::create([
            'name' => 'Carla CEO',
            'email' => 'ceo@tasknest.test',
            'password' => $password,
            'role' => 'ceo',
            'designation' => 'Chief Executive Officer',
        ]);

        $alice = User::create([
            'name' => 'Alice Nguyen',
            'email' => 'alice@tasknest.test',
            'password' => $password,
            'role' => 'member',
            'designation' => 'Senior Developer',
        ]);

        $bob = User::create([
            'name' => 'Bob Martinez',
            'email' => 'bob@tasknest.test',
            'password' => $password,
            'role' => 'member',
            'designation' => 'QA Engineer',
        ]);

        $carol = User::create([
            'name' => 'Carol Smith',
            'email' => 'carol@tasknest.test',
            'password' => $password,
            'role' => 'member',
            'designation' => 'UI/UX Designer',
        ]);

        $dave = User::create([
            'name' => 'Dave Thompson',
            'email' => 'dave@tasknest.test',
            'password' => $password,
            'role' => 'member',
            'designation' => 'Backend Developer',
        ]);

        $erin = User::create([
            'name' => 'Erin O\'Brien',
            'email' => 'erin@tasknest.test',
            'password' => $password,
            'role' => 'member',
            'designation' => 'DevOps Engineer',
        ]);

        // --- Projects ---

        $website = Project::create([
            'name' => 'Website Relaunch',
            'description' => 'Rebuild the company marketing site with a new design system.',
            'created_by' => $director->id,
        ]);
        $website->members()->attach([
            $alice->id => ['project_role' => 'lead'],
            $carol->id => ['project_role' => 'lead'],
            $bob->id => ['project_role' => 'member'],
        ]);

        $mobileApp = Project::create([
            'name' => 'TaskNest Mobile App',
            'description' => 'Flutter client for the internal task management platform.',
            'created_by' => $ceo->id,
        ]);
        $mobileApp->members()->attach([
            $dave->id => ['project_role' => 'lead'],
            $alice->id => ['project_role' => 'member'],
            $erin->id => ['project_role' => 'member'],
        ]);

        $infra = Project::create([
            'name' => 'Infrastructure Hardening',
            'description' => 'Improve deployment pipelines, monitoring, and backup strategy.',
            'created_by' => $director->id,
        ]);
        $infra->members()->attach([
            $erin->id => ['project_role' => 'lead'],
            $bob->id => ['project_role' => 'member'],
        ]);

        // --- Tasks ---

        $homepage = Task::create([
            'project_id' => $website->id,
            'title' => 'Design new homepage layout',
            'description' => 'Create high-fidelity mockups for the new homepage.',
            'status' => 'done',
            'priority' => 'high',
            'due_date' => now()->subDays(5),
            'assignee_id' => $carol->id,
            'created_by' => $director->id,
        ]);

        $nav = Task::create([
            'project_id' => $website->id,
            'title' => 'Implement responsive navigation',
            'description' => 'Build the new nav bar with mobile menu support.',
            'status' => 'in_progress',
            'priority' => 'medium',
            'due_date' => now()->addDays(3),
            'assignee_id' => $alice->id,
            'created_by' => $director->id,
        ]);

        Task::create([
            'project_id' => $website->id,
            'title' => 'QA pass on landing pages',
            'description' => null,
            'status' => 'todo',
            'priority' => 'medium',
            'due_date' => now()->addDays(7),
            'assignee_id' => $bob->id,
            'created_by' => $director->id,
        ]);

        $login = Task::create([
            'project_id' => $mobileApp->id,
            'title' => 'Build login screen',
            'description' => 'Email/password login using Sanctum token auth.',
            'status' => 'in_progress',
            'priority' => 'high',
            'due_date' => now()->addDays(2),
            'assignee_id' => $dave->id,
            'created_by' => $ceo->id,
        ]);

        Task::create([
            'project_id' => $mobileApp->id,
            'title' => 'Implement task list screen',
            'description' => 'Filterable list of tasks by project and status.',
            'status' => 'todo',
            'priority' => 'high',
            'due_date' => now()->addDays(10),
            'assignee_id' => $alice->id,
            'created_by' => $ceo->id,
        ]);

        Task::create([
            'project_id' => $mobileApp->id,
            'title' => 'Set up CI pipeline for Flutter build',
            'description' => null,
            'status' => 'todo',
            'priority' => 'low',
            'due_date' => null,
            'assignee_id' => $erin->id,
            'created_by' => $ceo->id,
        ]);

        Task::create([
            'project_id' => $infra->id,
            'title' => 'Set up automated DB backups',
            'description' => 'Daily backups with 30-day retention.',
            'status' => 'done',
            'priority' => 'high',
            'due_date' => now()->subDays(10),
            'assignee_id' => $erin->id,
            'created_by' => $director->id,
        ]);

        Task::create([
            'project_id' => $infra->id,
            'title' => 'Add uptime monitoring and alerts',
            'description' => null,
            'status' => 'in_progress',
            'priority' => 'medium',
            'due_date' => now()->addDays(5),
            'assignee_id' => $erin->id,
            'created_by' => $director->id,
        ]);

        Task::create([
            'project_id' => $infra->id,
            'title' => 'Load test the staging environment',
            'description' => null,
            'status' => 'todo',
            'priority' => 'low',
            'due_date' => null,
            'assignee_id' => $bob->id,
            'created_by' => $director->id,
        ]);

        // --- Labels, subtasks, comments, activity, notifications (demo data) ---

        $design = Label::create(['project_id' => $website->id, 'name' => 'Design', 'color' => 'purple']);
        $frontend = Label::create(['project_id' => $website->id, 'name' => 'Frontend', 'color' => 'blue']);
        $urgent = Label::create(['project_id' => $mobileApp->id, 'name' => 'Urgent', 'color' => 'red']);
        $homepage->labels()->attach($design->id);
        $nav->labels()->attach([$frontend->id, $design->id]);
        $login->labels()->attach($urgent->id);

        $position = 0;
        foreach (['Desktop layout' => true, 'Mobile menu' => true, 'Keyboard navigation' => false, 'Cross-browser test' => false, 'Accessibility review' => false] as $title => $done) {
            Subtask::create(['task_id' => $nav->id, 'title' => $title, 'is_done' => $done, 'position' => $position++]);
        }

        Comment::create(['task_id' => $nav->id, 'user_id' => $director->id, 'body' => 'Please make sure the menu works with a keyboard.']);
        Comment::create(['task_id' => $nav->id, 'user_id' => $alice->id, 'body' => 'On it, desktop layout is done and the mobile menu is in review.']);
        Comment::create(['task_id' => $login->id, 'user_id' => $ceo->id, 'body' => 'Can we get this demoed on Friday?']);

        $log = fn (User $u, string $type, Task $t, array $meta) => Activity::create([
            'user_id' => $u->id, 'subject_type' => 'Task', 'subject_id' => $t->id,
            'project_id' => $t->project_id, 'type' => $type, 'meta' => $meta + ['title' => $t->title],
        ]);
        $log($director, 'task.created', $nav, []);
        $log($alice, 'task.status_changed', $nav, ['from' => 'todo', 'to' => 'in_progress']);
        $log($carol, 'task.status_changed', $homepage, ['from' => 'in_progress', 'to' => 'done']);
        $log($ceo, 'task.created', $login, []);
        $log($dave, 'task.status_changed', $login, ['from' => 'todo', 'to' => 'in_progress']);

        $alice->notify(new TaskNestNotification('task_comment', "Daniel Director commented on '{$nav->title}'", $nav->id, $website->id));
        $alice->notify(new TaskNestNotification('task_assigned', "You were assigned 'Implement task list screen'", null, $mobileApp->id));
        $dave->notify(new TaskNestNotification('task_comment', "Carla CEO commented on '{$login->title}'", $login->id, $mobileApp->id));
    }
}
