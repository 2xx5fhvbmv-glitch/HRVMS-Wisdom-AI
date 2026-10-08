<?php

namespace App\Console\Commands\Demo;

use App\Support\Demo\DemoReset;
use Illuminate\Console\Command;

class DemoResetCommand extends Command
{
    protected $signature = 'demo:reset {--dry-run : Show what would be removed, change nothing} {--seed= : Rebuild with this seed (reproduce a run)} {--force : Skip the confirmation}';

    protected $description = 'Wipe the Demo ENV resort and build it again (DEMO_MODE only)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if (!$dryRun && !$this->option('force') && !$this->confirm('Wipe ALL Demo ENV data (including anything clients changed) and rebuild it?')) {
            return self::FAILURE;
        }
        try {
            $report = (new DemoReset)->run($dryRun, $this->option('seed') ? (int) $this->option('seed') : null, fn ($m) => $this->line($m));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->info("Dry run — {$report['planned']} rows would be removed:");
            foreach ($report['plan'] ?? [] as $table => $n) {
                $this->line("  {$table}: {$n}");
            }
            return self::SUCCESS;
        }
        $this->info("Done in {$report['seconds']}s — removed {$report['deleted']} rows, seed {$report['seed']}.");
        foreach ($report['verify']['checks'] as $c) {
            $this->line(($c['ok'] ? '  <info>PASS</info> ' : '  <error>FAIL</error> ') . $c['name'] . ($c['detail'] ? " — {$c['detail']}" : ''));
        }
        return $report['verify']['passed'] ? self::SUCCESS : self::FAILURE;
    }
}
