<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use DB;
use App\Models\ApplicationLink;
use App\Models\TAnotificationChild;

class DisableAdvertismentExpiredLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'links:JobAdvertisment-disable-expired';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable links where the expiry date is today';

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

        $affectedRows = ApplicationLink::whereDate('link_Expiry_date', $today)->get();
        foreach($affectedRows as $row){


            $taChild = TAnotificationChild::find($row->ta_child_id );

            if ($taChild) {
                $taChild->update(['status' => 'Expired']);
            }
        }
        $affectedRows->each->delete();
        $this->info("$affectedRows links Removed successfully where expiry date is today ($today).");
    }
}
