<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Helpers\Common;
use Carbon\Carbon;
use DB;

class OnboardingNewEmpHirePushNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'links:onboarding-new-emp-hire-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send push notification for onboarding new employee hire';

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
        $today  =   Carbon::today()->toDateString();
        // dd($today);
        $emp    =   Employee::join('resort_admins', 'resort_admins.id', '=', 'employees.Admin_Parent_id')
                        ->where('joining_date', $today)
                        // ->where('status', 'Active')
                        ->get()->map(function ($employee) {

                            $emp    =   Employee::where('id', '!=', $employee->id)
                                            ->where('resort_id', $employee->resort_id)
                                            ->where('status', 'Active')
                                            ->get()->map(function ($emp) use ($employee) {
                                                $title                                  =   "Announcement: New Employee Hired";
                                                $body                                   =   "welcome our new team member!" . " " . $employee->first_name . " " . $employee->last_name . " who has joined us today.";
                                                $moduleName                             =   'Announcement';
                    
                                                //Send in app notification to the team member
                                                Common::sendMobileNotification($employee->resort_id,2, null,null, $title,$body,$moduleName,[$emp->id],null);
                                                if($emp->device_token != null && $emp->device_token != '') {
                                                    //Send in push notification in mobile app
                                                    $data = Common::sendPushNotifictionForMobile([$emp->device_token], $title, $body, $moduleName,NULL,NULL,NULL,NULL);
                                                } 
                                            })->toArray();
                        });
    }
    
}