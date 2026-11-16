<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->data['kind'] ?? null,
            'message' => $this->data['message'] ?? '',
            'task_id' => $this->data['task_id'] ?? null,
            'project_id' => $this->data['project_id'] ?? null,
            'read' => $this->read_at !== null,
            'created_at' => $this->created_at,
        ];
    }
}
