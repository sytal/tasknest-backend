<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddProjectMemberRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectMemberRoleRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * List projects visible to the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Project::query()
            ->with(['creator', 'members'])
            ->withCount([
                'tasks',
                'tasks as tasks_done_count' => fn ($q) => $q->where('status', 'done'),
            ]);

        $query->withCount(['tasks as overdue_count' => fn ($q) => $q->where('status', '!=', 'done')->whereDate('due_date', '<', today())]);

        if ($user->isMember()) {
            $query->whereRelation('members', 'users.id', $user->id);
        }
        if (! $request->boolean('archived')) {
            $query->where('status', 'active');
        }

        return ProjectResource::collection($this->paged($query->latest(), $request));
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $data = $request->validated();

        $project = Project::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'color' => $data['color'] ?? null,
            'created_by' => $request->user()->id,
        ]);
        ActivityLogger::log('project.created', $project, $project->id, ['name' => $project->name]);

        foreach ($data['members'] ?? [] as $member) {
            $project->members()->attach($member['user_id'], [
                'project_role' => $member['project_role'] ?? 'member',
            ]);
        }

        $project->load(['creator', 'members']);

        return response()->json(['data' => new ProjectResource($project)], 201);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $project->load(['creator', 'members'])->loadCount([
            'tasks',
            'tasks as tasks_done_count' => fn ($q) => $q->where('status', 'done'),
        ]);

        return response()->json(['data' => new ProjectResource($project)]);
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $this->ensureWritable($project);
        $project->update($request->validated());
        ActivityLogger::log('project.updated', $project, $project->id, ['name' => $project->name]);
        $project->load(['creator', 'members']);

        return response()->json(['data' => new ProjectResource($project)]);
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->json(['message' => 'Project deleted successfully.']);
    }

    public function addMember(AddProjectMemberRequest $request, Project $project): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $data = $request->validated();

        $this->ensureWritable($project);
        $project->members()->syncWithoutDetaching([
            $data['user_id'] => ['project_role' => $data['project_role'] ?? 'member'],
        ]);
        $member = User::find($data['user_id']);
        ActivityLogger::log('member.added', $project, $project->id, ['member' => $member?->name]);
        Notifier::send($member, 'project_added', "You were added to '{$project->name}'", null, $project->id);

        $project->load('members');

        return response()->json(['data' => new ProjectResource($project)]);
    }

    public function removeMember(Request $request, Project $project, int $userId): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $this->ensureWritable($project);
        $project->members()->detach($userId);
        if ($request->boolean('unassign_tasks')) {
            $project->tasks()->where('assignee_id', $userId)->where('status', '!=', 'done')
                ->get()->each(fn ($t) => $t->update(['assignee_id' => null]));
        }
        ActivityLogger::log('member.removed', $project, $project->id, ['member' => User::find($userId)?->name]);

        $project->load('members');

        return response()->json(['data' => new ProjectResource($project)]);
    }

    public function updateMemberRole(UpdateProjectMemberRoleRequest $request, Project $project, int $userId): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $this->ensureWritable($project);
        $role = $request->validated('project_role');
        $project->members()->updateExistingPivot($userId, ['project_role' => $role]);
        $member = User::find($userId);
        ActivityLogger::log('member.role_changed', $project, $project->id, ['member' => $member?->name, 'role' => $role]);
        Notifier::send($member, 'role_changed', "Your role in '{$project->name}' is now {$role}", null, $project->id);

        $project->load('members');

        return response()->json(['data' => new ProjectResource($project)]);
    }

    public function archive(Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        $this->authorize('lead', $project);

        return $this->setStatus($project, 'archived');
    }

    public function unarchive(Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        $this->authorize('lead', $project);

        return $this->setStatus($project, 'active');
    }

    private function setStatus(Project $project, string $status): JsonResponse
    {
        $project->update(['status' => $status]);
        ActivityLogger::log($status === 'archived' ? 'project.archived' : 'project.unarchived', $project, $project->id, ['name' => $project->name]);
        $project->load(['creator', 'members']);

        return response()->json(['data' => new ProjectResource($project)]);
    }
}
