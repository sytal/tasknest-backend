<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Task;
use App\Services\ActivityLogger;
use App\Services\Notifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /** Newest first; clients render oldest-to-newest and load earlier pages on demand. */
    public function index(Request $request, Task $task)
    {
        $this->authorize('view', $task);

        return CommentResource::collection(
            $this->paged($task->comments()->with('author')->latest('id'), $request, always: true)
        );
    }

    public function store(StoreCommentRequest $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task);
        $this->ensureWritable($task->project);

        $user = $request->user();
        $comment = $task->comments()->create(['user_id' => $user->id, 'body' => $request->validated('body')]);
        ActivityLogger::log('task.commented', $task, $task->project_id, ['title' => $task->title]);

        $msg = "{$user->name} commented on '{$task->title}'";
        Notifier::send($task->assignee, 'task_comment', $msg, $task->id, $task->project_id);
        if ($task->created_by !== $task->assignee_id) {
            Notifier::send($task->creator, 'task_comment', $msg, $task->id, $task->project_id);
        }

        return response()->json(['data' => new CommentResource($comment->load('author'))], 201);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);
        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }
}
