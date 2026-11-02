<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddProjectMemberRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectMemberRoleRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * List projects visible to the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Project::query()
            ->with(['creator', 'members'])
            ->withCount([
                'tasks',
                'tasks as tasks_done_count' => fn ($q) => $q->where('status', 'done'),
            ]);

        if ($user->isMember()) {
            $query->whereRelation('members', 'users.id', $user->id);
        }

        return response()->json([
            'data' => ProjectResource::collection($query->latest()->get()),
        ]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $data = $request->validated();

        $project = Project::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'created_by' => $request->user()->id,
        ]);

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

        $project->update($request->validated());
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

        $project->members()->syncWithoutDetaching([
            $data['user_id'] => ['project_role' => $data['project_role'] ?? 'member'],
        ]);

        $project->load('members');

        return response()->json(['data' => new ProjectResource($project)]);
    }

    public function removeMember(Request $request, Project $project, int $userId): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $project->members()->detach($userId);

        $project->load('members');

        return response()->json(['data' => new ProjectResource($project)]);
    }

    public function updateMemberRole(UpdateProjectMemberRoleRequest $request, Project $project, int $userId): JsonResponse
    {
        $this->authorize('manageMembers', $project);

        $project->members()->updateExistingPivot($userId, [
            'project_role' => $request->validated('project_role'),
        ]);

        $project->load('members');

        return response()->json(['data' => new ProjectResource($project)]);
    }
}
