<?php

declare(strict_types=1);

use App\Models\RequestMetric;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('/health', static fn () => response()->json([
    'status' => 'ok',
]));

Route::get('/ready', static function () {
    try {
        DB::connection()->getPdo();

        return response()->json([
            'database' => 'ok',
            'status' => 'ok',
        ]);
    } catch (Throwable) {
        return response()->json([
            'database' => 'unavailable',
            'status' => 'error',
        ], 503);
    }
});

Route::get('/metrics', static function () {
    return view('metrics', [
        'requestsTotal' => RequestMetric::query()->count(),
        'responseTimeSum' => (float) RequestMetric::query()->sum('response_time_ms'),
        'responseTimeAvg' => (float) RequestMetric::query()->avg('response_time_ms'),
        'responseTimeMax' => (float) RequestMetric::query()->max('response_time_ms'),
        'recentRequests' => RequestMetric::query()
            ->latest()
            ->limit(10)
            ->get(),
    ]);
});
