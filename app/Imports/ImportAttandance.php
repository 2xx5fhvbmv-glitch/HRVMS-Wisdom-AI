<?php

namespace App\Imports;

use DB;
use Auth;
use Hash;
use App\Helpers\Common;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Models\DutyRoster;

use Illuminate\Support\Str;
use App\Models\ResortDepartment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;


use App\Models\ShiftSettings;
use App\Models\ParentAttendace;
use App\Models\ChildAttendace;
use App\Models\LeaveCategory;
use App\Models\EmployeeLeave;
class ImportAttandance implements  ToModel, WithHeadingRow
{

    protected $departmentId;
    protected $positionId;
    protected $resort;

    public function __construct()
    {
        // $this->departmentId = $departmentId;
        // $this->positionId = $positionId;
        $this->resort= Auth::guard('resort-admin')->user();
    }

    public function model(array $row)
    {

        // Check if the date is a numeric Excel date or a string date
        $dateValue = isset($row['date']) ? ltrim($row['date'], "'") : null;

        // Skip if date is empty or null
        if (empty($dateValue)) {
            return null;
        }

        // Handle both Excel serial dates and string dates
        if (is_numeric($dateValue)) {
            try {
                $date = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue))->format('Y-m-d');
            } catch (\Exception $e) {
                // If parsing fails, skip this row
                return null;
            }
        } else {
            // Try to parse as various date formats
            try {
                // Try DD-MM-YYYY format first (as shown in the image)
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $dateValue)) {
                    $date = Carbon::createFromFormat('d-m-Y', $dateValue)->format('Y-m-d');
                }
                // Try YYYY-MM-DD format
                elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)) {
                    $date = Carbon::createFromFormat('Y-m-d', $dateValue)->format('Y-m-d');
                }
                // Try DD/MM/YYYY format
                elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dateValue)) {
                    $date = Carbon::createFromFormat('d/m/Y', $dateValue)->format('Y-m-d');
                }
                // Default: try Carbon's auto-parsing
                else {
                    $date = Carbon::parse($dateValue)->format('Y-m-d');
                }
            } catch (\Exception $e) {
                // If parsing fails, skip this row
                return null;
            }
        }


        $employee = Employee::join('resort_admins as t1',"t1.id","=","employees.Admin_Parent_id")
                        ->where('t1.resort_id',$this->resort->resort_id)
                        ->where('employees.Emp_id',$row['employee_id'])
                        ->first(['employees.id']);



                        $shiftdata = ShiftSettings::where('resort_id',$this->resort->resort_id)
                        ->where("ShiftName",$row['shift'])
                        ->first(['id','TotalHours']);


            $overtime = is_numeric($row['overtime'])? Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['overtime']))->format('H:i') : null;
            $status =  $row['status'];
            $leaveType = $status == "ShortLeave" || $status == "HalfDayLeave" || $status == "FullDayLeave" ? $row['leavetype'] : null;
            $row['check_in_time'] = is_numeric($row['check_in_time'])? Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['check_in_time']))->format('H:i') : $row['check_in_time']; // Fallback if it's not a numeric time
            $row['check_out_time'] = is_numeric($row['check_out_time'])? Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['check_out_time']))->format('H:i') : $row['check_out_time']; // Fallback if it's not a numeric time
            $check_in_time = $row['check_in_time'];
            $check_out_time = $row['check_out_time'];

        if(isset($employee) && $shiftdata && $employee->id && $shiftdata->id)
        {


            if (isset($overtime) && $overtime != "00:00") {
                $shiftTotalMinutes = $this->convertTimeToMinutes($shiftdata->TotalHours);
                $overtimeMinutes = $this->convertTimeToMinutes($overtime);

                $totalMinutes = $shiftTotalMinutes + $overtimeMinutes;
                $TotalHours = $this->convertMinutesToTime($totalMinutes);
            } else {
                $shiftTotalMinutes = $this->convertTimeToMinutes($shiftdata->TotalHours);
                $TotalHours = $this->convertMinutesToTime($shiftTotalMinutes);
            }
            if($status =="DayOff")
            {
                $DayOfDate="DayOff";
            }
            else
            {
                $DayOfDate='';
            }
            // Use the already parsed $date to create Carbon object
            $date_obj = Carbon::parse($date);
            $start_date = $date_obj->copy()->startOfMonth()->format('Y-m-d');
            $end_date = $date_obj->copy()->endOfMonth()->format('Y-m-d');
            $newdate = $date_obj->format('m-d-Y')."-".$date_obj->format('m-d-Y');

            $DutyRoster = DutyRoster::updateOrCreate([
                    "ShiftDate"=>      $newdate,
                    "resort_id"=>$this->resort->resort_id,
                    "Shift_id"=>$shiftdata->id,
                    "Emp_id"=>$employee->id
                ],[
                "resort_id"=>$this->resort->resort_id,
                "Shift_id"=>$shiftdata->id,
                "Emp_id"=>$employee->id,
                "ShiftDate"=> $date."-".$date,
                "Year"=>date('Y',strtotime($date)),
                "DayOfDate"=> $DayOfDate,
                ]);
            if(isset($DutyRoster))
            {
              $parentAttandance = ParentAttendace::updateOrCreate(
                [
                    'Emp_id' => $employee->id,
                    'date' => $date,
                    'shift_id' => $shiftdata->id],
                    [
                    'roster_id'=>$DutyRoster->id,
                    'resort_id'=>$this->resort->resort_id,
                    'Emp_id'=>$employee->id,
                    'CheckingTime'=>isset($check_in_time)?$check_in_time:"00:00",
                    'CheckingOutTime'=>isset($check_out_time) ?  $check_out_time :  "00:00",
                    'OverTime'=> $overtime,
                    'DayWiseTotalHours'=>$TotalHours,
                    'Status'=> $status,
                    'Shift_id'=>$shiftdata->id,
                    'date'=> $date,
                    'CheckInCheckOut_Type'=> 'Manual',
                ]);


                if (isset($parentAttandance) && isset($check_out_time) && isset($check_in_time))
                {

                    ChildAttendace::updateOrCreate(
                        ['Parent_attd_id' => $parentAttandance->id],
                        [
                            'Parent_attd_id' => $parentAttandance->id,
                            'InTime_out' => $check_in_time,
                            'OutTime_out' => $check_out_time
                        ]
                    );
                }
            }
        }

    }


    // Helper functions to convert time to minutes and minutes back to time
    private function convertTimeToMinutes($time) {
        list($hours, $minutes) = explode(":", $time);
        return ($hours * 60) + $minutes;
    }

    private function convertMinutesToTime($minutes) {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        return str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($remainingMinutes, 2, '0', STR_PAD_LEFT);
    }
}

