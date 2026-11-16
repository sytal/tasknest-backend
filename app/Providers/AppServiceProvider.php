<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Project;
use App\Policies\CommentPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Auth\Notifications\ResetPassword;
use App\Models\Task;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);

        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(5)->by(strtolower((string) $r->input('email')).'|'.$r->ip()));
        RateLimiter::for('forgot', fn (Request $r) => Limit::perMinute(3)->by($r->ip()));
        RateLimiter::for('comments', fn (Request $r) => Limit::perMinute(30)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('avatar', fn (Request $r) => Limit::perHour(10)->by($r->user()?->id ?: $r->ip()));

        ResetPassword::createUrlUsing(fn ($user, string $token) => rtrim((string) config('app.frontend_url'), '/').'/#/reset-password?token='.$token.'&email='.urlencode($user->email));

        // This app is API-only: never redirect unauthenticated requests to a
        // "login" web route, always let the JSON exception renderer handle it.
        Authenticate::redirectUsing(fn () => null);
    }
}
