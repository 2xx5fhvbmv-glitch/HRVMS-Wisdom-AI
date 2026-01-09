<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TrainingSchedule;
use Carbon\Carbon;


class UpdateLearningStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'training:update-status';
    protected $description = 'Update training statuses based on dates';

    /**
     * The console command description.
     *
     * @var string
     */
    

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
        $today = Carbon::today()->toDateString();

        LearningRequest::where('start_date', $today)
            ->where('status', 'Scheduled')
            ->update(['status' => 'Ongoing']);

        LearningRequest::where('end_date', $today)
            ->where('status', 'Ongoing')
            ->update(['status' => 'Completed']);

        $this->info('Learning request statuses updated successfully.');
    }
}
