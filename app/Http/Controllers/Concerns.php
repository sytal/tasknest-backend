<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use App\Support\Limits;

trait Concerns
{
    /**
     * Paginate (per_page default 20, max 100) when the client sends page or
     * per_page; otherwise return everything (keeps small pickers simple).
     */
    protected function paged(mixed $query, Request $request, bool $always = false): mixed
    {
        if (! $always && ! $request->hasAny(['page', 'per_page'])) {
            return $query->get();
        }
        $per = min(max((int) $request->query('per_page', Limits::PER_PAGE_DEFAULT), 1), Limits::PER_PAGE_MAX);

        return $query->paginate($per);
    }

    protected function ensureWritable(Project $project): void
    {
        abort_if($project->status === 'archived', 422, 'This project is archived and read-only.');
    }
}
