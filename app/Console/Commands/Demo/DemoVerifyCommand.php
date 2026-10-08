<?php

namespace App\Console\Commands\Demo;

use App\Support\Demo\Demo;
use App\Support\Demo\DemoVerifier;
use Illuminate\Console\Command;

class DemoVerifyCommand extends Command
{
    protected $signature = 'demo:verify';

    protected $description = 'Check the Demo ENV flows work (PASS/FAIL per check)';

    public function handle(): int
    {
        if (!Demo::enabled()) {
            $this->error('Demo mode is off (DEMO_MODE=false).');
            return self::FAILURE;
        }
        $result = (new DemoVerifier)->run();
        foreach ($result['checks'] as $c) {
            $this->line(($c['ok'] ? '<info>PASS</info> ' : '<error>FAIL</error> ') . $c['name'] . ($c['detail'] ? " — {$c['detail']}" : ''));
        }
        return $result['passed'] ? self::SUCCESS : self::FAILURE;
    }
}
