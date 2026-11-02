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

use App\Models\Project;
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

        Task::create([
            'project_id' => $website->id,
            'title' => 'Design new homepage layout',
            'description' => 'Create high-fidelity mockups for the new homepage.',
            'status' => 'done',
            'priority' => 'high',
            'due_date' => now()->subDays(5),
            'assignee_id' => $carol->id,
            'created_by' => $director->id,
        ]);

        Task::create([
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

        Task::create([
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
    }
}
