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
use App\Helpers\Common;

class CheckEmoployeeExtraWorkd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'weekly:CheckExtraHours';


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
        $today = $now->format('d/m/Y');
    
      
        $weekStartDate = $now->copy()->startOfWeek()->format('Y-m-d');
        $weekEndDate = $now->copy()->endOfWeek()->format('Y-m-d');
        $ShiftTotalWeekHours = 48; // Default to 48 hours for the week
            $employees = Employee::where('is_employee', 1)
                ->where('status', 'Active')
                ->with(['EmployeeAttandance.Getshift','position', 'department','resortAdmin'])
                ->whereHas('resortAdmin', function ($query) {
                    $query->where('status','Active');
                })
                ->whereHas('EmployeeAttandance', function ($query) use($weekStartDate, $weekEndDate) {
                    $query->whereBetween('date', [$weekStartDate, $weekEndDate]);
                })
                ->get()
                ->map(function($ak) use ($weekStartDate, $weekEndDate) 
                {

                
                    $ExcludingOtExtraHours = 0;

                    $ak->EmployeeAttandance->whereBetween('date', [$weekStartDate, $weekEndDate])->each(function($attendance) use (&$ExcludingOtExtraHours) {
                        if ($attendance->Getshift) 
                        {
                        $shiftTotalHours = $attendance->Getshift ? Carbon::parse($attendance->Getshift->TotalHours): 0;
                            if ($shiftTotalHours)
                            {
                                $shiftTotalHours = $shiftTotalHours->format('H:i');
                                $shiftTotalHours = Carbon::parse($shiftTotalHours)->diffInMinutes(Carbon::parse('00:00')) / 60;
                            } 
                            else 
                            {
                                $shiftTotalHours = 0; // Default to 0 if no shift hours are set
                            }
                            $SumOfTotalDailyWorkingHours = 0;
                            $checkin = Carbon::parse($attendance->CheckingTime);
                            $checkout = Carbon::parse($attendance->CheckingOutTime);
                            
                            if ($checkout->lt($checkin))
                            {
                                $checkout->addDay(); 
                            }
                            $SumOfTotalDailyWorkingHours = $checkout->diffInMinutes($checkin) / 60;
                            $SumOfTotalDailyWorkingHours = round($SumOfTotalDailyWorkingHours, 2);
                    

                        $overtimeHours = 0;

                        $DayWiseTotalHours = Carbon::parse( $attendance->DayWiseTotalHours)->diffInMinutes(Carbon::parse('00:00')) / 60;

                        if ($SumOfTotalDailyWorkingHours > $shiftTotalHours && $shiftTotalHours > 0) 
                        {
                            $overtimeHours = $SumOfTotalDailyWorkingHours - $shiftTotalHours;

                           
                        }

                            $ExcludingOtExtraHours += $overtimeHours;
                        }
                    });
                    $ak->ExcludingOtExtraHours = $ExcludingOtExtraHours;

                    $ak->PositionName = $ak->position ? $ak->position->position_title : 'N/A';
                    $ak->DepartmentName = $ak->department ? $ak->department->name : 'N/A';
                    $ak->EmployeeName = $ak->resortAdmin->first_name . ' ' . $ak->resortAdmin->last_name;
                    $ak->Employee_id = $ak->Emp_id;
                    return $ak;
                })->filter(function($ak) use($ShiftTotalWeekHours)
                {
                    return $ak->ExcludingOtExtraHours > $ShiftTotalWeekHours;
                });

   
        if ($employees->isNotEmpty()) 
        {
             foreach($employees as $emp)
             {            
                          
                  $compliance = Compliance::firstOrCreate([
                        'resort_id' => $emp->resort_id,
                        'employee_id' => $emp->id,
                        'module_name' => 'Time and Attandance',
                        'compliance_breached_name' => '48 hours per week',
                        'description' => "Employee {$emp->EmployeeName}  ({$emp->PositionName}) is scheduled for {$emp->ExcludingOtExtraHours} hours this week. Adjust schedule to comply with the 48-hour weekly limit.",
                        'reported_on' => Carbon::now(),
                        'status' => 'Breached'
                ]);

              
            }
                
         $this->info('Time And Attandance Compliance Created successfully.');
        }
        else
        {
            $this->info('Time And Attandance No Any Compliance Found.');
        }

       


    }
}
