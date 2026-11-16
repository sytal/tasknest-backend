<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function task(Request $request, Task $task)
    {
        $this->authorize('view', $task);

        return ActivityResource::collection($this->paged(
            Activity::with('user')->where('subject_type', 'Task')->where('subject_id', $task->id)->latest('id'),
            $request,
            always: true,
        ));
    }

    public function project(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        return ActivityResource::collection($this->paged(
            Activity::with('user')->where('project_id', $project->id)->latest('id'),
            $request,
            always: true,
        ));
    }
}
