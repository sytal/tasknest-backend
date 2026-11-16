<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'due_date' => $this->due_date?->toDateString(),
            'color' => $this->color,
            'overdue_count' => $this->when(isset($this->overdue_count), $this->overdue_count, 0),
            'created_by' => $this->created_by,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'members' => UserResource::collection($this->whenLoaded('members')),
            'tasks_count' => $this->whenCounted('tasks'),
            'tasks_done_count' => $this->when(isset($this->tasks_done_count), $this->tasks_done_count, 0),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
