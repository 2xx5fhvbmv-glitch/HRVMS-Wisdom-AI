<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\Bccasing;
use App\Models\BuffingPlan;
use App\Helpers\Common;

class weekly_plan_rollover_command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weekly:plan_rollover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rolls over plans weekly by updating woyearweek for relevant records';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $currentYearWeek = Common::getCurrentYearWeek();
        $previousYearWeek = date('YW', strtotime(substr($currentYearWeek, 0, 4) . 'W' . substr($currentYearWeek, 4) . ' -1 week'));

        $bccasingsToUpdate = Bccasing::where([
            ['sstatus', 'wip'],
            ['wipMove', 0],
            ['woYearWeek', $previousYearWeek]
        ])->get();

        // dd($bccasingsToUpdate, $previousYearWeek);

        if($bccasingsToUpdate->count() > 0) {
            foreach ($bccasingsToUpdate as $key => $value) {
                // Check if plan for current week exists
                $plan = BuffingPlan::where([
                    ['woTyreSize', $value->woTyreSize],
                    ['woPattern', $value->woPattern],
                    ['woMould', $value->woMould],
                    ['woYearWeek', $currentYearWeek],
                ])->first();

                if(!$plan) {
                    $value->sstatus = 'free';
                }

                $value->woYearWeek = $currentYearWeek;
                $value->save();
            }
        }

        $this->info('Weekly plan rollover completed.');
    }
}
