<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->due_date?->toDateString(),
            'assignee_id' => $this->assignee_id,
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'start_date' => $this->start_date?->toDateString(),
            'estimate_hours' => $this->estimate_hours,
            'labels' => LabelResource::collection($this->whenLoaded('labels')),
            'subtasks' => SubtaskResource::collection($this->whenLoaded('subtasks')),
            'subtasks_count' => $this->whenCounted('subtasks'),
            'subtasks_done_count' => $this->when(isset($this->subtasks_done_count), $this->subtasks_done_count),
            'comments_count' => $this->whenCounted('comments'),
            'created_by' => $this->created_by,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
