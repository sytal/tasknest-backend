<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubtaskResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'task_id' => $this->task_id, 'title' => $this->title, 'is_done' => $this->is_done, 'position' => $this->position];
    }
}
