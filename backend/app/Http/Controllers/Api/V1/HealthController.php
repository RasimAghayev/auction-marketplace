<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'mongodb' => $this->checkMongoDB(),
        ];

        $healthy = array_reduce($checks, fn($carry, $check) => $carry && $check['healthy'], true);

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => now()->toISOString(),
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['healthy' => true, 'message' => 'Database connection OK'];
        } catch (\Throwable $e) {
            return ['healthy' => false, 'message' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            Redis::connection()->ping();
            return ['healthy' => true, 'message' => 'Redis connection OK'];
        } catch (\Throwable $e) {
            return ['healthy' => false, 'message' => $e->getMessage()];
        }
    }

    private function checkMongoDB(): array
    {
        try {
            $manager = new \MongoDB\Driver\Manager(config('database.connections.mongodb.dsn'));
            $command = new \MongoDB\Driver\Command(['ping' => 1]);
            $manager->executeCommand(config('database.connections.mongodb.database'), $command);
            return ['healthy' => true, 'message' => 'MongoDB connection OK'];
        } catch (\Throwable $e) {
            return ['healthy' => false, 'message' => $e->getMessage()];
        }
    }
}