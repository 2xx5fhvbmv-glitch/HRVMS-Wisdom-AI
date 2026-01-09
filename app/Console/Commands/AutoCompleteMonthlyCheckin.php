<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MonthlyCheckingModel;
use Carbon\Carbon;

class AutoCompleteMonthlyCheckin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monthly-check-in:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update monthly-check-in status based on dates';

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
        $now        = Carbon::now();
        $today      = $now->format('d/m/Y');

        MonthlyCheckingModel::where('date_discussion',$today)
                            ->whereRaw("STR_TO_DATE(end_time, '%H:%i') <= ?", [$now->copy()->subHour()->format('H:i')])
                            ->where('status', 'Confirm')
                            ->update(['status' => 'Conducted']);
            
        $this->info('Monthly Check-In status updated successfully.');
    }
}
