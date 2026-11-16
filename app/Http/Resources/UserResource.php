<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            // Role is a permission level: only director/CEO (and the user
            // themselves, for client-side permission checks) receive it.
            'role' => $this->when(
                ($viewer = $request->user()) === null || $viewer->isManagement() || $viewer->id === $this->id,
                fn () => $this->role,
            ),
            'designation' => $this->designation,
            'is_active' => (bool) $this->is_active,
            'avatar_url' => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null,
            'project_role' => $this->when(
                $this->pivot !== null,
                fn () => $this->pivot->project_role,
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
