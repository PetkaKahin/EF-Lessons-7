<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $method
 * @property string $path
 * @property int $status_code
 * @property float $response_time_ms
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class RequestMetric extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'method',
        'path',
        'status_code',
        'response_time_ms',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'response_time_ms' => 'float',
        ];
    }
}
