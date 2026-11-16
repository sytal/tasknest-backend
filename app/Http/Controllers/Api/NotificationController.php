<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return NotificationResource::collection(
            $this->paged($request->user()->notifications()->latest(), $request, always: true)
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['count' => $request->user()->unreadNotifications()->count()]);
    }

    public function read(Request $request, string $id): JsonResponse
    {
        $n = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $n->markAsRead();

        return response()->json(['data' => new NotificationResource($n)]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
