<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\IncidentsInvestigation;
use Carbon\Carbon;
use App\Models\Employee;
use Auth;
use App\Events\ResortNotificationEvent;
use App\Models\Compliance;
use App\Helpers\Common;
use App\Models\incidents;
class CheckIncidentCompliance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CheckHourly:IncidentCompliance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        
        $fourtieenEightHoursAgo = Carbon::now()->subHours(48);
        $employees= array();
        $incidents = incidents::with(['Investigation','victim.resortAdmin','victim.position','victim.department'])->where('priority', 'high')
                            ->where('severity', 'Severe')
                            ->where('created_at', '<=', $fourtieenEightHoursAgo)
                            ->whereHas( 'Investigation', function ($query) use ($fourtieenEightHoursAgo) {
                                 $query->where('Ministry_notified','No');
                            })
                            ->get()->map(function ($incident) use(&$employees) 
                            {
                               
                                $incident->Fullname = $incident->victim->resortAdmin->first_name . ' ' . $incident->victim->resortAdmin->last_name;
                                $incident->poistion = $incident->victim->position->position_title;
                                $incident->department = $incident->victim->department->name;
                               


                                   $employees[] = [
                                                    'resort_id' =>$incident->resort_id,
                                                    'employee_id' => $incident->victims,
                                                    'module_name' => 'Incident Compliance',
                                                    'compliance_breached_name' => 'Ministry is not Notified yet',
                                                    'description' => "High severity incident for {$incident->Fullname} was created more than 48 hours ago and ministry has not been notified yet.",
                                                    'reported_on' => Carbon::now(),
                                                    'status' => 'Breached'
                                                ];
                                return $incident;
                            });

        if(!empty($employees))
        {
            if($employees)
            {     
                $compliance = Compliance::insert($employees);

                $this->info('High severity incident compliance breached for '.count($employees).' employees.');
            }
            else
            {
                $this->error('No  compliance  Create For Incident.');
            }
        }
    }
}
