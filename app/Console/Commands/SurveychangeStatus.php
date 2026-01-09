<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ParentSurvey;
use Carbon\Carbon;
use DB;

class SurveychangeStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'links:survey-change-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Survey Status Updated';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $today = Carbon::today()->toDateString();
        ParentSurvey::where('Start_date', $today)->where('Status','Publish')->update(['Status' => 'OnGoing']);
        ParentSurvey::where('End_date', $today)->where('Status','OnGoing')->update(['Status' => 'Complete']);
    }
}  