<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['project_id', 'name', 'color'])]
class Label extends Model
{
    public const PALETTE = ['red', 'orange', 'amber', 'green', 'teal', 'blue', 'purple', 'pink', 'gray'];

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }
}
