<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        if (array_key_exists('designation', $data)) {
            $new = trim((string) $data['designation']);
            abort_if($new !== trim((string) $user->designation) && ! $user->isCeo(), 403, 'Only the CEO can change designations.');
            $data['designation'] = $new === '' ? null : $new;
        }
        $user->forceFill($data)->save();

        return response()->json(['user' => new UserResource($user)]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->forceFill(['password' => $request->validated('password')])->save();
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Password updated.']);
    }
}
