<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubtaskRequest;
use App\Http\Resources\SubtaskResource;
use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Http\JsonResponse;

class SubtaskController extends Controller
{
    public function store(SubtaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task);
        $this->authorize('manageSubtasks', $task);
        $this->ensureWritable($task->project);

        $subtask = $task->subtasks()->create([
            'title' => $request->validated('title'),
            'position' => (int) $task->subtasks()->max('position') + 1,
        ]);

        return response()->json(['data' => new SubtaskResource($subtask)], 201);
    }

    public function update(SubtaskRequest $request, Task $task, Subtask $subtask): JsonResponse
    {
        abort_unless($subtask->task_id === $task->id, 404);
        $this->authorize('view', $task);
        $this->ensureWritable($task->project);

        $data = $request->validated();
        if (isset($data['title']) || isset($data['position'])) {
            $this->authorize('manageSubtasks', $task);
        } else {
            $this->authorize('toggleSubtask', $task);
        }
        $subtask->update($data);

        return response()->json(['data' => new SubtaskResource($subtask)]);
    }

    public function destroy(Task $task, Subtask $subtask): JsonResponse
    {
        abort_unless($subtask->task_id === $task->id, 404);
        $this->authorize('view', $task);
        $this->authorize('manageSubtasks', $task);
        $this->ensureWritable($task->project);
        $subtask->delete();

        return response()->json(['message' => 'Subtask deleted.']);
    }
}
