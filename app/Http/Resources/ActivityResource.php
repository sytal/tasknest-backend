<?php

namespace App\Http\Resources;

use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'message' => ActivityLogger::message($this->resource),
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'project_id' => $this->project_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at,
        ];
    }
}
