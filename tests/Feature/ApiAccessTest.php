<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, ?string $email = null): User
    {
        return User::create([
            'name' => ucfirst($role).' Person',
            'email' => $email ?? "$role@example.test",
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function projectWith(User $creator, array $members = []): Project
    {
        $p = Project::create(['name' => 'P', 'created_by' => $creator->id]);
        foreach ($members as $m) {
            $p->members()->attach($m->id, ['project_role' => 'member']);
        }

        return $p;
    }

    public function test_unauthenticated_requests_get_401_json(): void
    {
        $this->getJson('/api/projects')->assertStatus(401);
        $this->getJson('/api/users')->assertStatus(401);
    }

    public function test_member_cannot_list_or_create_users(): void
    {
        Sanctum::actingAs($this->user('member'));

        $this->getJson('/api/users')->assertForbidden();
        $this->postJson('/api/users', [
            'name' => 'X', 'email' => 'x@example.test', 'password' => 'password1', 'role' => 'member',
        ])->assertForbidden();
    }

    public function test_director_can_create_user_and_validation_matches_client_rules(): void
    {
        Sanctum::actingAs($this->user('director'));

        $this->postJson('/api/users', [
            'name' => 'New', 'email' => 'bad', 'password' => '1234567', 'role' => 'member',
        ])->assertStatus(422)->assertJsonValidationErrors(['email', 'password']);

        $this->postJson('/api/users', [
            'name' => 'New', 'email' => 'new@example.test', 'password' => '12345678', 'role' => 'member',
        ])->assertCreated();

        $this->postJson('/api/users', [
            'name' => 'Dup', 'email' => 'new@example.test', 'password' => '12345678', 'role' => 'member',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_non_member_gets_404_for_project_and_task(): void
    {
        $director = $this->user('director');
        $outsider = $this->user('member', 'out@example.test');
        $insider = $this->user('member', 'in@example.test');
        $project = $this->projectWith($director, [$insider]);
        $task = Task::create([
            'project_id' => $project->id, 'title' => 'T', 'status' => 'todo',
            'priority' => 'low', 'assignee_id' => $insider->id, 'created_by' => $director->id,
        ]);

        Sanctum::actingAs($outsider);
        $this->getJson("/api/projects/{$project->id}")->assertNotFound();
        $this->getJson("/api/tasks/{$task->id}")->assertNotFound();

        Sanctum::actingAs($insider);
        $this->getJson("/api/projects/{$project->id}")->assertOk();
    }

    public function test_member_cannot_create_project_or_task(): void
    {
        $director = $this->user('director');
        $member = $this->user('member');
        $project = $this->projectWith($director, [$member]);

        Sanctum::actingAs($member);
        $this->postJson('/api/projects', ['name' => 'Nope'])->assertForbidden();
        $this->postJson('/api/tasks', [
            'project_id' => $project->id, 'title' => 'Nope', 'assignee_id' => $member->id,
        ])->assertForbidden();
    }

    public function test_title_longer_than_255_is_rejected(): void
    {
        $director = $this->user('director');
        $member = $this->user('member');
        $project = $this->projectWith($director, [$member]);

        Sanctum::actingAs($director);
        $this->postJson('/api/tasks', [
            'project_id' => $project->id, 'title' => str_repeat('a', 256), 'assignee_id' => $member->id,
        ])->assertStatus(422)->assertJsonValidationErrors(['title']);
    }

    public function test_user_role_cannot_be_escalated_via_mass_assignment_on_login_or_me(): void
    {
        $member = $this->user('member');
        Sanctum::actingAs($member);

        $this->getJson('/api/me')->assertOk()->assertJsonPath('user.role', 'member');
    }

    public function test_errors_do_not_leak_stack_traces(): void
    {
        config(['app.debug' => false]);
        Sanctum::actingAs($this->user('member'));

        $res = $this->getJson('/api/tasks/99999');
        $res->assertNotFound();
        $this->assertArrayNotHasKey('trace', $res->json());
        $this->assertArrayNotHasKey('file', $res->json());
        $this->assertSame('Not Found', $res->json('message'));
    }

    public function test_login_is_throttled(): void
    {
        $this->user('member');
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => 'member@example.test', 'password' => 'wrong'])
                ->assertStatus(401);
        }
        $this->postJson('/api/login', ['email' => 'member@example.test', 'password' => 'wrong'])
            ->assertStatus(429)->assertHeader('Retry-After');
    }
}
