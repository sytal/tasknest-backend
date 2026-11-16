<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Http\Resources\UserResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $user = $request->user();
        $term = '%'.addcslashes($request->query('q'), '%_').'%';
        $member = fn ($q) => $user->isMember() ? $q->whereRelation('members', 'users.id', $user->id) : $q;

        $projects = $member(Project::query())->where('name', 'like', $term)->limit(10)->get();
        $tasks = Task::query()->with(['project', 'assignee'])
            ->when($user->isMember(), fn ($q) => $q->whereHas('project', fn ($p) => $p->whereRelation('members', 'users.id', $user->id)))
            ->where('title', 'like', $term)->limit(10)->get();
        $users = $user->isManagement()
            ? User::where(fn ($w) => $w->where('name', 'like', $term)->orWhere('email', 'like', $term))->limit(10)->get()
            : collect();

        return response()->json([
            'projects' => ProjectResource::collection($projects),
            'tasks' => TaskResource::collection($tasks),
            'users' => UserResource::collection($users),
        ]);
    }
}
