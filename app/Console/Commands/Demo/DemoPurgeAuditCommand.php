<?php

namespace App\Console\Commands\Demo;

use App\Support\Demo\DemoPurger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Run after adding tables: the demo reset refuses until every table is classified. */
class DemoPurgeAuditCommand extends Command
{
    protected $signature = 'demo:purge-audit';

    protected $description = 'List tables the Demo ENV reset cannot classify (config/demo_purge.php)';

    public function handle(): int
    {
        $problems = (new DemoPurger((int) DB::table('resorts')->value('id')))->audit();
        if (!$problems) {
            $this->info('Every table is classified — the demo reset can run.');
            return self::SUCCESS;
        }
        foreach ($problems as $p) {
            $this->error($p);
        }
        return self::FAILURE;
    }
}
