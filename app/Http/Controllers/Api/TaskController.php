<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBulkAssignRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TaskController extends Controller
{
    /**
     * List tasks visible to the authenticated user, filterable by
     * project_id, assignee, and status.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $tasks = QueryBuilder::for(Task::class)
            ->allowedFilters(
                AllowedFilter::exact('project_id'),
                AllowedFilter::exact('assignee_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('priority'),
            )
            ->allowedSorts('due_date', 'priority', 'created_at')
            ->with(['project', 'assignee', 'creator'])
            ->when($user->isMember(), function ($query) use ($user) {
                $query->whereHas('project', function ($projectQuery) use ($user) {
                    $projectQuery->whereRelation('members', 'users.id', $user->id);
                });
            })
            ->latest()
            ->get();

        return response()->json(['data' => TaskResource::collection($tasks)]);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $this->authorize('create', Task::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['status'] = $data['status'] ?? 'todo';
        $data['priority'] = $data['priority'] ?? 'medium';

        $task = Task::create($data);
        $task->load(['project', 'assignee', 'creator']);

        return response()->json(['data' => new TaskResource($task)], 201);
    }

    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        $task->load(['project', 'assignee', 'creator']);

        return response()->json(['data' => new TaskResource($task)]);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $task->update($request->validated());
        $task->load(['project', 'assignee', 'creator']);

        return response()->json(['data' => new TaskResource($task)]);
    }

    /**
     * A member may update the status of a task assigned to them,
     * without being able to reassign or otherwise edit it.
     */
    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse
    {
        $task->update(['status' => $request->validated('status')]);
        $task->load(['project', 'assignee', 'creator']);

        return response()->json(['data' => new TaskResource($task)]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully.']);
    }

    /**
     * Bulk-assign tasks within a project: a single assignee gets every task,
     * or multiple assignees are round-robined across the given task_ids in order.
     */
    public function bulkAssign(StoreBulkAssignRequest $request, Project $project): JsonResponse
    {
        $this->authorize('bulkAssign', Task::class);

        $data = $request->validated();
        $taskIds = $data['task_ids'];
        $assigneeIds = $data['assignee_ids'];
        $assigneeCount = count($assigneeIds);

        DB::transaction(function () use ($taskIds, $assigneeIds, $assigneeCount) {
            foreach ($taskIds as $index => $taskId) {
                $assigneeId = $assigneeIds[$index % $assigneeCount];
                Task::whereKey($taskId)->update(['assignee_id' => $assigneeId]);
            }
        });

        $tasks = Task::whereIn('id', $taskIds)
            ->with(['project', 'assignee', 'creator'])
            ->get();

        return response()->json([
            'data' => TaskResource::collection($tasks),
            'meta' => [
                'updated_count' => $tasks->count(),
                'assignee_count' => $assigneeCount,
            ],
        ]);
    }
}
