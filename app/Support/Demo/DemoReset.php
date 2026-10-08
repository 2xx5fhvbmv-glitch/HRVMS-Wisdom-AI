<?php

namespace App\Support\Demo;

use App\Helpers\StorageHelper;
use Database\Seeders\Demo\DemoEnvSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Wipe the Demo ENV resort's data and build it again: purge → seed → verify.
 * Used by `php artisan demo:reset` and the super-admin reset button.
 * Nothing else ever wipes the demo (spec §2.3).
 */
class DemoReset
{
    public function run(bool $dryRun = false, ?int $seed = null, ?callable $say = null): array
    {
        $say ??= fn () => null;
        if (!Demo::enabled()) {
            throw new RuntimeException('Demo mode is off (DEMO_MODE=false).');
        }
        if (empty(config('demo.password'))) {
            throw new RuntimeException('Set DEMO_PASSWORD in .env — every demo login uses it.');
        }
        $started = microtime(true);
        $seed ??= random_int(1, 999999);
        $report = ['seed' => $seed, 'dry_run' => $dryRun, 'started_at' => now()->toDateTimeString(), 'deleted' => 0];

        $resort = DB::table('resorts')->where('resort_id', config('demo.resort_code'))->first();
        if ($resort) {
            Demo::assertSafeToReset();
            $purger = new DemoPurger($resort->id);
            $plan = $purger->plan();
            $report['planned'] = array_sum($plan);
            if ($dryRun) {
                $report['plan'] = $plan;
                return $report;
            }
            $say("Removing {$report['planned']} demo rows…");
            $report['deleted'] = array_sum($purger->purge());
            $this->deleteFiles();
        } elseif ($dryRun) {
            $report['planned'] = 0;
            return $report;
        }

        $say("Building Demo ENV (seed {$seed})…");
        $report['seeded'] = (new DemoEnvSeeder)->seed($seed, $say);
        Demo::refresh();
        $say('Verifying…');
        $report['verify'] = (new DemoVerifier)->run();
        $report['seconds'] = round(microtime(true) - $started, 1);
        $report['finished_at'] = now()->toDateTimeString();
        Cache::forever('demo:last_reset', $report);
        Log::info('Demo ENV reset', ['seed' => $seed, 'deleted' => $report['deleted'], 'passed' => $report['verify']['passed']]);
        return $report;
    }

    /** The demo resort's uploaded files live under its code; nothing outside that prefix is touched. */
    private function deleteFiles(): void
    {
        $code = config('demo.resort_code');
        if (!$code || $code !== DB::table('resorts')->where('resort_id', $code)->value('resort_id')) {
            return;
        }
        try {
            StorageHelper::disk()->deleteDirectory($code);
        } catch (\Throwable $e) {
            Log::warning('Demo ENV: could not delete demo files: ' . $e->getMessage());
        }
    }
}
