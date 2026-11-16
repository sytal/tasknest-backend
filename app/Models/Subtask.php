<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['task_id', 'title', 'is_done', 'position'])]
class Subtask extends Model
{
    protected function casts(): array
    {
        return ['is_done' => 'boolean'];
    }
}
