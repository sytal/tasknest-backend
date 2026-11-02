<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * List users, for picking task assignees. Director/CEO only.
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->isManagement(), 403, 'Only director or CEO may access user listings.');

        return response()->json([
            'data' => UserResource::collection(User::orderBy('name')->get()),
        ]);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()->isManagement(), 403, 'Only director or CEO may access user listings.');

        return response()->json(['data' => new UserResource($user)]);
    }

    /**
     * Create a new user account (director/CEO only). There is no public
     * registration endpoint by design — accounts are provisioned internally.
     */
    public function store(CreateUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return response()->json(['data' => new UserResource($user)], 201);
    }
}
