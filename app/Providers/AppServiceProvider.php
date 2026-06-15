<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Task;
use App\Policies\TaskPolicy;
use App\Repositories\EloquentTaskRepository;
use App\Repositories\TaskRepositoryInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            TaskRepositoryInterface::class,
            EloquentTaskRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Task::class, TaskPolicy::class);

        RateLimiter::for('api', static function (Request $request): Limit {
            $limit = max(1, (int) config('security.api_rate_limit_per_minute', 60));
            $key = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute($limit)->by((string) $key);
        });
    }
}
