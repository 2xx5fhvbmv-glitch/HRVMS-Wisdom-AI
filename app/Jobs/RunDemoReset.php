<?php

namespace App\Jobs;

use App\Support\Demo\DemoReset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * The super-admin "Reset Demo ENV" button. A reset can take minutes, longer than
 * the database queue's retry_after (90s), so the queue may hand this job out a
 * second time while it runs: the lock makes that second copy a no-op, and
 * unlimited tries keep it from being marked failed meanwhile.
 */
class RunDemoReset implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $timeout = 1800;
    public $tries = 0;

    public function handle(): void
    {
        $lock = Cache::lock('demo-env-reset', 1800);
        if (!$lock->get()) {
            return;
        }
        $started = now()->toDateTimeString();
        $status = fn (string $state, string $message) => Cache::forever('demo:reset_status',
            ['state' => $state, 'started_at' => $started, 'updated_at' => now()->toDateTimeString(), 'message' => $message]);
        try {
            $status('running', 'Starting…');
            $report = (new DemoReset)->run(false, null, fn ($m) => $status('running', $m));
            $status('done', $report['verify']['passed']
                ? "Reset finished in {$report['seconds']}s — every check passed."
                : "Reset finished in {$report['seconds']}s — some checks FAILED, see below.");
        } catch (\Throwable $e) {
            Log::error('Demo ENV reset failed', ['error' => $e->getMessage(), 'at' => $e->getFile() . ':' . $e->getLine()]);
            $status('failed', 'Reset failed: ' . $e->getMessage());
        } finally {
            $lock->release();
        }
    }
}
