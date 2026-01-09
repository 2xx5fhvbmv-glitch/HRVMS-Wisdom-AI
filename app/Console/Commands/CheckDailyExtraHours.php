<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ShiftSettings;
use App\Models\Employee;
use Carbon\Carbon;
use App\Models\ParentAttendace;
use Auth;
use App\Events\ResortNotificationEvent;
use App\Models\Compliance;
use App\Models\BreakAttendaces;
use App\Helpers\Common;
class CheckDailyExtraHours extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Daily:CheckExtraHours';

    protected $description = 'Checks for employees who have worked extra hours this week';

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
        $today = $now->format('Y-m-d');
        $ShiftTotalWeekHours = 48; // Default to 48 hours for the week
        $employeesWithoutBreak = [];
            $employees = Employee::where('is_employee', 1)
                ->where('status', 'Active')
                ->with(['EmployeeAttandance.Getshift','position', 'department','resortAdmin'])
                ->whereHas('resortAdmin', function ($query) {
                    $query->where('status','Active');
                })
                ->whereHas('EmployeeAttandance', function ($query) use($today) {
                    $query->where('date',$today);
                })
                ->get()

                ->map(function($ak) use ($today,$employeesWithoutBreak) 
                    {

                    
                        $ExcludingOtExtraHours = 0;

                        $ak->EmployeeAttandance->where('date',$today)->each(function($attendance) use (&$ExcludingOtExtraHours,&$ak,$employeesWithoutBreak) 
                        {
            
                            $breaks = BreakAttendaces::where('Parent_attd_id', $attendance->id)->get();
                                             
                     
                            $checkInTime = Carbon::parse($attendance->CheckingTime);
                            if ($breaks->isEmpty()) 
                            {   
                           
                              $hoursWorked = $checkInTime->diffInHours(Carbon::now());
                            
                                    if ($hoursWorked >= 5) 
                                    {

                                        array_push($employeesWithoutBreak, [
                                            'resort_id' => $attendance->resort_id,
                                            'employee_id' => $attendance->Emp_id,
                                            'module_name' => 'Time & Attendance',
                                            'compliance_breached_name' => 'Without Mandatory Break',
                                            'description' => "Employee {$ak->resortAdmin->first_name}{$ak->resortAdmin->last_name}  ({$ak->position->position_title}) worked {$hoursWorked} hours without a break. Review schedule to ensure adherence to labor laws.",
                                            'reported_on' => Carbon::now(),
                                            'status' => 'Breached'
                                        ]);

                                     

                                                
                                    }
                                } 
                                else 
                                {
                                    $lastBreakTime = $checkInTime;
                                    $hadLongPeriod = false;
                                    
                                    foreach ($breaks as $break) 
                                    {
                                        $breakStartTime = Carbon::parse($break->start_time);
                                        $timeDifference = $lastBreakTime->diffInHours($breakStartTime);
                                        if ($timeDifference >= 5) 
                                        {
                                                $hadLongPeriod = true;
                                                $employee = Employee::find($attendance->Emp_id);
                                                 array_push($employeesWithoutBreak, [
                                                    'resort_id' => $attendance->resort_id,
                                                    'employee_id' => $attendance->Emp_id,
                                                    'module_name' => 'Time & Attendance',
                                                    'compliance_breached_name' => 'Without Mandatory Break',
                                                    'description' => "Employee {$ak->resortAdmin->first_name}{$ak->resortAdmin->last_name}  ({$ak->position->position_title}) worked {$timeDifference} hours without a break. Review schedule to ensure adherence to labor laws.",
                                                    'reported_on' => Carbon::now(),
                                                    'status' => 'Breached'
                                                ]);
                                                    
                                        }
                                        
                                        $lastBreakTime = Carbon::parse($break->end_time);
                                }
                            }
                              $ak->employeesWithoutBreak = $employeesWithoutBreak;
                        });
                        $ak->ExcludingOtExtraHours = $ExcludingOtExtraHours;

                        $ak->PositionName = $ak->position ? $ak->position->position_title : 'N/A';
                        $ak->DepartmentName = $ak->department ? $ak->department->name : 'N/A';
                        $ak->EmployeeName = $ak->resortAdmin->first_name . ' ' . $ak->resortAdmin->last_name;
                        $ak->Employee_id = $ak->Emp_id;
                      
                        return $ak;
                    })->filter(function($ak) {
                        return !empty($ak->employeesWithoutBreak);
                    });


         if (!empty($employees)) 
         {
            foreach($employees as $emp)
            {
            
                Compliance::insert($emp->employeesWithoutBreak);
            }
                
         $this->info('Time And Attandance Compliance Created successfully.');
        }
        else
        {
            $this->info('Time And Attandance No Any Compliance Found.');
        }

    }
}
