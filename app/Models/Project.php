<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'created_by', 'status', 'due_date', 'color'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['due_date' => 'date'];
    }

    /**
     * Members of this project (through the pivot), with their per-project role.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('project_role')
            ->withTimestamps();
    }

    public function leads(): BelongsToMany
    {
        return $this->members()->wherePivot('project_role', 'lead');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function labels(): HasMany
    {
        return $this->hasMany(Label::class);
    }

    public function isLead(int $userId): bool
    {
        return $this->members()->where('users.id', $userId)->wherePivot('project_role', 'lead')->exists();
    }

    public function hasMember(int $userId): bool
    {
        return $this->members()->where('users.id', $userId)->exists();
    }
}
