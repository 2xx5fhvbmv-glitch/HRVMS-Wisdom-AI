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
        // Was an exact-date match (=), so a single missed run (cron not
        // wired up, server down, etc.) permanently stranded a survey in its
        // old status forever — confirmed against live data (surveys still
        // "Publish" with an End_date months in the past). Using <= lets a
        // late run catch up on every survey that should already have
        // transitioned, not just today's. End_date also now matches either
        // Publish or OnGoing so a survey that never made it to OnGoing (both
        // transitions missed) still reaches Complete in one pass instead of
        // needing two separate catch-up runs.
        $today = Carbon::today()->toDateString();
        ParentSurvey::where('Start_date', '<=', $today)->where('Status', 'Publish')->update(['Status' => 'OnGoing']);
        ParentSurvey::where('End_date', '<=', $today)->whereIn('Status', ['Publish', 'OnGoing'])->update(['Status' => 'Complete']);
    }
}  