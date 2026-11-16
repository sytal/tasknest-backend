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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TaskController extends Controller
{
    /** @var array<int, string> */
    private const WITH = ['project', 'assignee', 'creator', 'labels'];

    /**
     * Tasks visible to the user. Filters: project_id, assignee_id, status,
     * priority, label (id); params: mine=1, due_from, due_to, q.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = QueryBuilder::for(Task::class)
            ->allowedFilters(
                AllowedFilter::exact('project_id'),
                AllowedFilter::exact('assignee_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('priority'),
                AllowedFilter::callback('label', fn (Builder $q, $v) => $q->whereHas('labels', fn ($l) => $l->where('labels.id', $v))),
            )
            ->allowedSorts('due_date', 'priority', 'created_at')
            ->with(self::WITH)
            ->withCount(['subtasks', 'subtasks as subtasks_done_count' => fn ($q) => $q->where('is_done', true)])
            ->when($user->isMember(), fn ($q) => $q->whereHas('project', fn ($p) => $p->whereRelation('members', 'users.id', $user->id)))
            ->when($request->boolean('mine'), fn ($q) => $q->where('assignee_id', $user->id))
            ->when($request->filled('due_from'), fn ($q) => $q->whereDate('due_date', '>=', $request->date('due_from')))
            ->when($request->filled('due_to'), fn ($q) => $q->whereDate('due_date', '<=', $request->date('due_to')))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.addcslashes((string) $request->query('q'), '%_').'%'))
            ->latest();

        return TaskResource::collection($this->paged($query, $request));
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $this->authorize('create', Task::class);
        $this->ensureWritable(Project::findOrFail($request->validated('project_id')));

        $data = $request->validated();
        $labelIds = $data['label_ids'] ?? null;
        unset($data['label_ids']);
        $data['created_by'] = $request->user()->id;
        $data['status'] = $data['status'] ?? 'todo';
        $data['priority'] = $data['priority'] ?? 'medium';

        $task = Task::create($data);
        if ($labelIds !== null) {
            $task->labels()->sync($this->projectLabelIds($task, $labelIds));
        }

        return response()->json(['data' => new TaskResource($this->full($task))], 201);
    }

    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        return response()->json(['data' => new TaskResource($this->full($task))]);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);
        $this->ensureWritable($task->project);

        $data = $request->validated();
        $labelIds = $data['label_ids'] ?? null;
        unset($data['label_ids']);

        $task->update($data);
        if ($labelIds !== null) {
            $task->labels()->sync($this->projectLabelIds($task, $labelIds));
        }

        return response()->json(['data' => new TaskResource($this->full($task))]);
    }

    /**
     * A member may update the status of a task assigned to them,
     * without being able to reassign or otherwise edit it.
     */
    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse
    {
        $this->ensureWritable($task->project);
        $task->update(['status' => $request->validated('status')]);

        return response()->json(['data' => new TaskResource($this->full($task))]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);
        $this->ensureWritable($task->project);

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
        $this->ensureWritable($project);

        $data = $request->validated();
        $taskIds = $data['task_ids'];
        $assigneeIds = $data['assignee_ids'];
        $assigneeCount = count($assigneeIds);

        DB::transaction(function () use ($taskIds, $assigneeIds, $assigneeCount) {
            foreach ($taskIds as $index => $taskId) {
                Task::find($taskId)?->update(['assignee_id' => $assigneeIds[$index % $assigneeCount]]);
            }
        });

        $tasks = Task::whereIn('id', $taskIds)->with(self::WITH)->get();

        return response()->json([
            'data' => TaskResource::collection($tasks),
            'meta' => ['updated_count' => $tasks->count(), 'assignee_count' => $assigneeCount],
        ]);
    }

    private function full(Task $task): Task
    {
        return $task->load([...self::WITH, 'subtasks'])
            ->loadCount(['comments', 'subtasks', 'subtasks as subtasks_done_count' => fn ($q) => $q->where('is_done', true)]);
    }

    /**
     * Only labels belonging to the task's project may be attached.
     *
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    private function projectLabelIds(Task $task, array $ids): array
    {
        return $task->project->labels()->whereIn('id', $ids)->pluck('id')->all();
    }
}
