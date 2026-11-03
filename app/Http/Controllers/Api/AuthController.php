<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UploadAvatarRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    /**
     * Log a user in and issue a Sanctum personal access token.
     *
     * Credentials are checked via Auth::validate(), which compares the
     * bcrypt hash through the User model's "hashed" password cast, so a
     * missing user and a wrong password both fall through to the same
     * generic 401 response below — no detail is leaked either way.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (! Auth::validate([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ])) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $user = User::where('email', $credentials['email'])->first();

        $token = $user->createToken($credentials['device_name'] ?? 'tasknest-mobile');

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token->plainTextToken,
        ]);
    }

    /**
     * Revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Return the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }

    /**
     * Upload (or replace) the authenticated user's avatar.
     */
    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $extension = $request->file('avatar')->getClientOriginalExtension();
        $path = $request->file('avatar')->storeAs('avatars', "{$user->id}.{$extension}", 'public');

        $user->update(['avatar_path' => $path]);

        return response()->json(['data' => new UserResource($user)]);
    }

    /**
     * Remove the authenticated user's avatar.
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        return response()->json(['data' => new UserResource($user)]);
    }
}
