<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\TaskResource;
use App\Models\Activity;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $visible = fn ($q) => $user->isMember()
            ? $q->whereHas('project', fn ($p) => $p->whereRelation('members', 'users.id', $user->id))
            : $q;
        $projectIds = Project::query()
            ->when($user->isMember(), fn ($q) => $q->whereRelation('members', 'users.id', $user->id))
            ->where('status', 'active')->pluck('id');

        $tasks = fn () => $visible(Task::query()->whereIn('project_id', $projectIds));

        $dueSoon = Task::query()->with(['project', 'assignee', 'labels'])
            ->where('assignee_id', $user->id)->where('status', '!=', 'done')
            ->whereIn('project_id', $projectIds)
            ->whereNotNull('due_date')->whereDate('due_date', '<=', today()->addDays(7))
            ->orderBy('due_date')->limit(5)->get();

        $payload = [
            'stats' => [
                'projects' => $projectIds->count(),
                'open_tasks' => $tasks()->where('status', '!=', 'done')->count(),
                'overdue' => $tasks()->where('status', '!=', 'done')->whereDate('due_date', '<', today())->count(),
                'my_open_tasks' => $tasks()->where('assignee_id', $user->id)->where('status', '!=', 'done')->count(),
            ],
            'my_due_soon' => TaskResource::collection($dueSoon),
            'recent_activity' => ActivityResource::collection(
                Activity::with('user')->whereIn('project_id', $projectIds)->latest('id')->limit(5)->get()
            ),
            'workload' => [],
        ];

        if ($user->isManagement()) {
            $payload['workload'] = User::where('is_active', true)
                ->withCount(['assignedTasks as open_tasks' => fn ($q) => $q->where('status', '!=', 'done')])
                ->orderByDesc('open_tasks')->limit(10)->get()
                ->map(fn ($u) => ['user_id' => $u->id, 'name' => $u->name, 'open_tasks' => $u->open_tasks]);
        }

        return response()->json($payload);
    }
}
