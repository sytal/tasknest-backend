<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLabelRequest;
use App\Http\Resources\LabelResource;
use App\Models\Label;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class LabelController extends Controller
{
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        return LabelResource::collection($project->labels()->orderBy('name')->get());
    }

    public function store(StoreLabelRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);
        $this->authorize('lead', $project);
        $this->ensureWritable($project);

        return response()->json(['data' => new LabelResource($project->labels()->create($request->validated()))], 201);
    }

    public function destroy(Project $project, Label $label): JsonResponse
    {
        $this->authorize('view', $project);
        $this->authorize('lead', $project);
        abort_unless($label->project_id === $project->id, 404);
        $label->delete();

        return response()->json(['message' => 'Label deleted.']);
    }
}
