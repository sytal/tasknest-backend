<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Director/CEO only. Filters: search, role. Paginated when page/per_page given.
     */
    public function index(Request $request)
    {
        $this->management($request);

        $query = User::orderBy('name')
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->query('role')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.addcslashes((string) $request->query('search'), '%_').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('email', 'like', $term));
            });

        return UserResource::collection($this->paged($query, $request));
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->management($request);

        return response()->json(['data' => new UserResource($user)]);
    }

    /**
     * No public registration; accounts are provisioned internally.
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return response()->json(['data' => new UserResource($user)], 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->guard($request, $user);
        $data = $request->validated();

        if (isset($data['role']) && $data['role'] !== $user->role) {
            abort_if($user->id === $request->user()->id, 422, 'You cannot change your own role.');
            abort_if($user->isManagement() && $data['role'] === 'member' && $this->lastManager($user), 422, 'At least one active director or CEO is required.');
        }
        $user->forceFill($data)->save();

        return response()->json(['data' => new UserResource($user)]);
    }

    public function deactivate(Request $request, User $user): JsonResponse
    {
        $this->guard($request, $user);
        abort_if($user->id === $request->user()->id, 422, 'You cannot deactivate yourself.');
        abort_if($user->isManagement() && $this->lastManager($user), 422, 'At least one active director or CEO is required.');

        $user->forceFill(['is_active' => false])->save();
        $user->tokens()->delete();

        return response()->json(['data' => new UserResource($user)]);
    }

    public function activate(Request $request, User $user): JsonResponse
    {
        $this->guard($request, $user);
        $user->forceFill(['is_active' => true])->save();

        return response()->json(['data' => new UserResource($user)]);
    }

    /**
     * Sets a temporary password, revokes sessions and returns it once.
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $this->guard($request, $user);
        $temp = Str::password(12, symbols: false);
        $user->forceFill(['password' => $temp])->save();
        $user->tokens()->delete();

        return response()->json(['data' => new UserResource($user), 'temporary_password' => $temp]);
    }

    private function management(Request $request): void
    {
        abort_unless($request->user()->isManagement(), 403, 'Only director or CEO may access user management.');
    }

    /**
     * Policy: management only; a director cannot modify a CEO account.
     */
    private function guard(Request $request, User $target): void
    {
        $this->management($request);
        abort_if($target->isCeo() && ! $request->user()->isCeo(), 403, 'Only a CEO can modify a CEO account.');
    }

    private function lastManager(User $user): bool
    {
        return ! User::where('id', '!=', $user->id)->where('is_active', true)->whereIn('role', ['director', 'ceo'])->exists();
    }
}
