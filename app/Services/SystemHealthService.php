<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

class SystemHealthService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        return [
            'application' => [
                'status' => 'ok',
                'environment' => config('app.env'),
                'url' => config('app.url'),
                'locale' => config('app.locale'),
            ],
            'database' => $this->databaseStatus(),
            'queue' => [
                'status' => 'ok',
                'driver' => config('queue.default'),
                'worker_connection' => config('operations.shared_hosting.queue_worker.connection'),
            ],
            'cache' => [
                'status' => 'ok',
                'store' => config('cache.default'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function databaseStatus(): array
    {
        try {
            DB::connection()->select('SELECT 1');

            return [
                'status' => 'ok',
                'connection' => (string) config('database.default'),
                'message' => 'Database connection is healthy.',
            ];
        } catch (Throwable $throwable) {
            return [
                'status' => 'fail',
                'connection' => (string) config('database.default'),
                'message' => $throwable->getMessage(),
            ];
        }
    }
}
