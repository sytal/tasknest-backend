<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LabelController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SubtaskController;
use App\Support\Limits;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'app' => config('app.name')]);
});

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:forgot');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:forgot');
Route::get('/meta', fn () => response()->json(['data' => Limits::all()]));

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [ProfileController::class, 'update']);
    Route::put('/me/password', [ProfileController::class, 'changePassword']);
    Route::post('/me/avatar', [AuthController::class, 'uploadAvatar'])->middleware('throttle:avatar');
    Route::delete('/me/avatar', [AuthController::class, 'deleteAvatar']);

    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate']);
    Route::post('/users/{user}/activate', [UserController::class, 'activate']);
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);

    Route::get('/dashboard', DashboardController::class);
    Route::get('/search', SearchController::class);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read']);

    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);
    Route::put('/projects/{project}', [ProjectController::class, 'update']);
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
    Route::post('/projects/{project}/members', [ProjectController::class, 'addMember']);
    Route::delete('/projects/{project}/members/{userId}', [ProjectController::class, 'removeMember']);
    Route::put('/projects/{project}/members/{userId}', [ProjectController::class, 'updateMemberRole']);

    Route::post('/projects/{project}/archive', [ProjectController::class, 'archive']);
    Route::post('/projects/{project}/unarchive', [ProjectController::class, 'unarchive']);
    Route::get('/projects/{project}/activity', [ActivityController::class, 'project']);
    Route::get('/projects/{project}/labels', [LabelController::class, 'index']);
    Route::post('/projects/{project}/labels', [LabelController::class, 'store']);
    Route::delete('/projects/{project}/labels/{label}', [LabelController::class, 'destroy']);

    Route::get('/tasks/{task}/comments', [CommentController::class, 'index']);
    Route::post('/tasks/{task}/comments', [CommentController::class, 'store'])->middleware('throttle:comments');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
    Route::get('/tasks/{task}/activity', [ActivityController::class, 'task']);
    Route::post('/tasks/{task}/subtasks', [SubtaskController::class, 'store']);
    Route::put('/tasks/{task}/subtasks/{subtask}', [SubtaskController::class, 'update']);
    Route::delete('/tasks/{task}/subtasks/{subtask}', [SubtaskController::class, 'destroy']);

    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
    Route::post('/projects/{project}/tasks/bulk-assign', [TaskController::class, 'bulkAssign']);
});
