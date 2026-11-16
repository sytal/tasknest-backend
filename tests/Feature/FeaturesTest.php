<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $director;
    private User $ceo;
    private User $lead;
    private User $member;
    private User $outsider;
    private Project $project;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        $mk = fn (string $role, string $n) => User::create(['name' => $n, 'email' => "$n@t.test", 'password' => 'password', 'role' => $role]);
        $this->director = $mk('director', 'dir');
        $this->ceo = $mk('ceo', 'ceo');
        $this->lead = $mk('member', 'lead');
        $this->member = $mk('member', 'mem');
        $this->outsider = $mk('member', 'out');
        $this->project = Project::create(['name' => 'P', 'created_by' => $this->director->id]);
        $this->project->members()->attach([
            $this->lead->id => ['project_role' => 'lead'],
            $this->member->id => ['project_role' => 'member'],
        ]);
        $this->task = Task::create([
            'project_id' => $this->project->id, 'title' => 'T', 'assignee_id' => $this->member->id,
            'created_by' => $this->director->id, 'due_date' => today()->addDay(),
        ]);
    }

    public function test_comments_flow_and_permissions(): void
    {
        Sanctum::actingAs($this->member);
        $id = $this->postJson("/api/tasks/{$this->task->id}/comments", ['body' => '  hi  '])
            ->assertCreated()->json('data.id');
        $this->postJson("/api/tasks/{$this->task->id}/comments", ['body' => '  '])->assertStatus(422);
        $this->postJson("/api/tasks/{$this->task->id}/comments", ['body' => str_repeat('a', 2001)])->assertStatus(422);
        $this->getJson("/api/tasks/{$this->task->id}/comments")->assertOk()->assertJsonPath('data.0.body', 'hi')->assertJsonStructure(['meta', 'links']);

        Sanctum::actingAs($this->outsider);
        $this->getJson("/api/tasks/{$this->task->id}/comments")->assertNotFound();
        $this->deleteJson("/api/comments/$id")->assertNotFound();

        $other = User::create(['name' => 'o', 'email' => 'o2@t.test', 'password' => 'password', 'role' => 'member']);
        $this->project->members()->attach($other->id, ['project_role' => 'member']);
        Sanctum::actingAs($other);
        $this->deleteJson("/api/comments/$id")->assertForbidden();

        Sanctum::actingAs($this->lead);
        $this->deleteJson("/api/comments/$id")->assertOk();
        $this->assertSame(0, Comment::count());
    }

    public function test_activity_and_notifications_on_changes(): void
    {
        Sanctum::actingAs($this->director);
        $this->putJson("/api/tasks/{$this->task->id}", ['status' => 'done', 'assignee_id' => $this->lead->id])->assertOk();

        $this->getJson("/api/tasks/{$this->task->id}/activity")->assertOk()
            ->assertJsonFragment(['type' => 'task.status_changed'])->assertJsonFragment(['type' => 'task.assigned']);
        $this->getJson("/api/projects/{$this->project->id}/activity")->assertOk()->assertJsonStructure(['meta']);

        Sanctum::actingAs($this->lead);
        $this->getJson('/api/notifications/unread-count')->assertJsonPath('count', 1);
        $nid = $this->getJson('/api/notifications')->assertOk()->json('data.0.id');
        $this->postJson("/api/notifications/$nid/read")->assertOk();
        $this->getJson('/api/notifications/unread-count')->assertJsonPath('count', 0);

        Sanctum::actingAs($this->outsider);
        $this->getJson("/api/projects/{$this->project->id}/activity")->assertNotFound();
        $this->postJson("/api/notifications/$nid/read")->assertNotFound();
    }

    public function test_due_reminders_are_idempotent(): void
    {
        $this->artisan('tasks:send-due-reminders')->assertSuccessful();
        $this->artisan('tasks:send-due-reminders')->assertSuccessful();
        $this->assertSame(1, $this->member->notifications()->where('data->kind', 'task_due_tomorrow')->count());
    }

    public function test_user_management_rules(): void
    {
        Sanctum::actingAs($this->member);
        $this->putJson("/api/users/{$this->lead->id}", ['name' => 'x'])->assertForbidden();
        $this->postJson("/api/users/{$this->lead->id}/deactivate")->assertForbidden();

        Sanctum::actingAs($this->director);
        $this->postJson("/api/users/{$this->director->id}/deactivate")->assertStatus(422);
        $this->putJson("/api/users/{$this->director->id}", ['role' => 'member'])->assertStatus(422);
        $this->putJson("/api/users/{$this->ceo->id}", ['name' => 'x'])->assertForbidden();
        $this->putJson("/api/users/{$this->lead->id}", ['role' => 'bogus'])->assertStatus(422);
        $this->putJson("/api/users/{$this->lead->id}", ['role' => 'member', 'is_active' => false, 'name' => 'New'])->assertOk();
        $this->assertTrue($this->lead->fresh()->is_active, 'is_active must not be mass-assignable via update');

        $this->lead->createToken('x');
        $this->postJson("/api/users/{$this->lead->id}/deactivate")->assertOk();
        $this->assertSame(0, $this->lead->tokens()->count());
        $this->postJson('/api/login', ['email' => 'lead@t.test', 'password' => 'password'])
            ->assertForbidden()->assertJsonPath('message', 'Account disabled. Contact your administrator.');

        $temp = $this->postJson("/api/users/{$this->member->id}/reset-password")->assertOk()->json('temporary_password');
        $this->postJson('/api/login', ['email' => 'mem@t.test', 'password' => $temp])->assertOk();

        $this->getJson('/api/users?search=mem&page=1')->assertOk()->assertJsonCount(1, 'data')->assertJsonStructure(['meta']);

        // last active manager protection
        Sanctum::actingAs($this->ceo);
        $this->postJson("/api/users/{$this->director->id}/deactivate")->assertOk();
        $this->ceo->update(['role' => 'ceo']);
        $this->member->forceFill(['role' => 'director'])->save();
        $this->postJson("/api/users/{$this->member->id}/deactivate")->assertOk();
        $this->putJson("/api/users/{$this->member->id}", ['role' => 'member'])->assertOk();
    }

    public function test_profile_password_and_avatar(): void
    {
        Sanctum::actingAs($this->member);
        $this->putJson('/api/me', ['name' => ''])->assertStatus(422);
        $this->putJson('/api/me', ['name' => 'New Name', 'designation' => 'Dev', 'role' => 'director'])->assertOk()->assertJsonPath('user.name', 'New Name');
        $this->assertSame('member', $this->member->fresh()->role);

        $this->putJson('/api/me/password', ['current_password' => 'wrong', 'password' => 'newpassword1', 'password_confirmation' => 'newpassword1'])->assertStatus(422);
        $this->putJson('/api/me/password', ['current_password' => 'password', 'password' => 'short', 'password_confirmation' => 'short'])->assertStatus(422);

        Storage::fake('public');
        $this->post('/api/me/avatar', ['avatar' => UploadedFile::fake()->create('a.pdf', 10)], ['Accept' => 'application/json'])->assertStatus(422);
        $this->post('/api/me/avatar', ['avatar' => UploadedFile::fake()->image('a.png')->size(3000)], ['Accept' => 'application/json'])->assertStatus(422);
        $this->post('/api/me/avatar', ['avatar' => UploadedFile::fake()->image('a.png', 800, 600)], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonStructure(['data' => ['avatar_url']]);
    }

    public function test_password_change_revokes_other_tokens(): void
    {
        $this->member->createToken('other');
        $token = $this->member->createToken('current')->plainTextToken;
        $this->withToken($token)->putJson('/api/me/password', [
            'current_password' => 'password', 'password' => 'newpassword1', 'password_confirmation' => 'newpassword1',
        ])->assertOk();
        $this->assertSame(1, $this->member->tokens()->count());
    }

    public function test_forgot_password_does_not_enumerate(): void
    {
        $a = $this->postJson('/api/forgot-password', ['email' => 'mem@t.test'])->assertOk()->json('message');
        $b = $this->postJson('/api/forgot-password', ['email' => 'nobody@t.test'])->assertOk()->json('message');
        $this->assertSame($a, $b);
        $this->postJson('/api/reset-password', ['email' => 'mem@t.test', 'token' => 'bad', 'password' => 'newpassword1', 'password_confirmation' => 'newpassword1'])->assertStatus(422);
    }

    public function test_labels_subtasks_and_task_fields(): void
    {
        Sanctum::actingAs($this->member);
        $this->postJson("/api/projects/{$this->project->id}/labels", ['name' => 'Bug', 'color' => 'red'])->assertForbidden();

        Sanctum::actingAs($this->lead);
        $this->postJson("/api/projects/{$this->project->id}/labels", ['name' => 'Bug', 'color' => 'neon'])->assertStatus(422);
        $label = $this->postJson("/api/projects/{$this->project->id}/labels", ['name' => 'Bug', 'color' => 'red'])->assertCreated()->json('data.id');
        $this->postJson("/api/projects/{$this->project->id}/labels", ['name' => 'Bug', 'color' => 'red'])->assertStatus(422);
        $sid = $this->postJson("/api/tasks/{$this->task->id}/subtasks", ['title' => 'S1'])->assertCreated()->json('data.id');

        Sanctum::actingAs($this->member);
        $this->putJson("/api/tasks/{$this->task->id}/subtasks/$sid", ['is_done' => true])->assertOk();
        $this->postJson("/api/tasks/{$this->task->id}/subtasks", ['title' => 'S2'])->assertForbidden();
        $this->assertSame('in_progress', $this->task->fresh()->status === 'todo' ? 'in_progress' : 'x', 'subtasks never auto-complete');
        $this->assertNotSame('done', $this->task->fresh()->status);

        Sanctum::actingAs($this->director);
        $this->putJson("/api/tasks/{$this->task->id}", ['label_ids' => [$label], 'estimate_hours' => 3.5, 'start_date' => '2026-01-10', 'due_date' => '2026-01-01'])->assertStatus(422);
        $this->putJson("/api/tasks/{$this->task->id}", ['label_ids' => [$label], 'estimate_hours' => 3.5])->assertOk()
            ->assertJsonPath('data.labels.0.name', 'Bug')->assertJsonPath('data.subtasks_count', 1)->assertJsonPath('data.subtasks_done_count', 1);
        $this->getJson("/api/tasks?filter[label]=$label")->assertJsonCount(1, 'data');
    }

    public function test_archived_project_is_read_only_and_hidden(): void
    {
        Sanctum::actingAs($this->lead);
        $this->postJson("/api/projects/{$this->project->id}/archive")->assertOk()->assertJsonPath('data.status', 'archived');
        $this->getJson('/api/projects')->assertJsonCount(0, 'data');
        $this->getJson('/api/projects?archived=1')->assertJsonCount(1, 'data');
        Sanctum::actingAs($this->member);
        $this->patchJson("/api/tasks/{$this->task->id}/status", ['status' => 'done'])->assertStatus(422);
        $this->postJson("/api/tasks/{$this->task->id}/comments", ['body' => 'x'])->assertStatus(422);
        Sanctum::actingAs($this->outsider);
        $this->postJson("/api/projects/{$this->project->id}/archive")->assertNotFound();
        Sanctum::actingAs($this->lead);
        $this->postJson("/api/projects/{$this->project->id}/unarchive")->assertOk();
    }

    public function test_dashboard_search_meta_and_calendar_filters(): void
    {
        $this->getJson('/api/meta')->assertOk()->assertJsonPath('data.password_min', 8);

        Sanctum::actingAs($this->member);
        $this->getJson('/api/dashboard')->assertOk()->assertJsonStructure(['stats' => ['projects', 'open_tasks', 'overdue'], 'my_due_soon', 'recent_activity', 'workload'])
            ->assertJsonPath('workload', []);
        $this->getJson('/api/search?q=T')->assertStatus(422);
        $r = $this->getJson('/api/search?q=ca')->assertOk();
        $this->assertSame([], $r->json('users'));
        $this->getJson('/api/tasks?mine=1&due_from='.today()->toDateString().'&due_to='.today()->addDays(2)->toDateString())->assertJsonCount(1, 'data');
        $this->getJson('/api/tasks?per_page=500&page=1')->assertJsonPath('meta.per_page', 100);

        Sanctum::actingAs($this->director);
        $this->getJson('/api/dashboard')->assertOk()->assertJsonCount(5, 'workload');
        $this->getJson('/api/search?q=me')->assertOk()->assertJsonCount(1, 'users');

        Sanctum::actingAs($this->outsider);
        $this->getJson("/api/tasks/{$this->task->id}")->assertNotFound();
        $this->getJson('/api/tasks')->assertJsonCount(0, 'data');
    }

    public function test_project_and_member_activity_and_validation(): void
    {
        Sanctum::actingAs($this->director);
        $this->postJson('/api/projects', ['name' => str_repeat('a', 256)])->assertStatus(422);
        $this->postJson('/api/projects', ['name' => 'N', 'description' => str_repeat('a', 2001)])->assertStatus(422);
        $id = $this->postJson('/api/projects', ['name' => 'N', 'color' => 'blue'])->assertCreated()->json('data.id');
        $this->postJson("/api/projects/$id/members", ['user_id' => $this->member->id, 'project_role' => 'lead'])->assertOk();
        $this->getJson("/api/projects/$id/activity")->assertJsonFragment(['type' => 'member.added'])->assertJsonFragment(['type' => 'project.created']);
        $this->postJson('/api/tasks', ['project_id' => $id, 'title' => 'x', 'assignee_id' => $this->member->id, 'status' => 'bogus'])->assertStatus(422);
        $this->postJson('/api/tasks', ['project_id' => $id, 'title' => 'x', 'assignee_id' => $this->member->id, 'start_date' => 'nope'])->assertStatus(422);
        $this->getJson('/api/nope')->assertNotFound()->assertJsonMissingPath('trace');
    }

    public function test_remove_member_can_unassign_open_tasks(): void
    {
        Sanctum::actingAs($this->director);
        $this->deleteJson("/api/projects/{$this->project->id}/members/{$this->member->id}?unassign_tasks=1")->assertOk();
        $this->assertNull($this->task->fresh()->assignee_id);
    }
}
