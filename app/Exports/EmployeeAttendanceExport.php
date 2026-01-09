<?php
namespace App\Exports;

use App\Models\ShiftSettings;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use App\Models\Employee;
use App\Models\LeaveCategory;
use App\Models\ResortBenifitGridChild;
class EmployeeAttendanceExport implements FromCollection, WithHeadings, WithEvents
{
    public $resort;
    public $startDate;
    public $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->resort = Auth::guard('resort-admin')->user();
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function headings(): array
    {
        return ['Date', 'Employee ID', 'Name','Shift' ,'Check-In Time', 'Check-Out Time', 'Overtime','Status','Rank','LeaveType'];

    }

    public function collection()
    {
        $employees = Employee::with('resortAdmin')
            ->where('resort_id', $this->resort->resort_id)
            ->get();

        // Fetch shift data
        $shifts = ShiftSettings::where('resort_id', $this->resort->resort_id)->get();

        $dates = collect();
        $currentDate = \Carbon\Carbon::createFromFormat('d/m/Y', $this->startDate);
        $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $this->endDate);

        // Generate date range dynamically
        while ($currentDate->lte($endDate)) {
            $dates->push($currentDate->format('d-m-Y'));
            $currentDate->addDay();
        }

        // Map employees to date range with shift info
        $result = collect();
        foreach ($dates as $date) {
            foreach ($employees as $employee) {
                $resortAdmin = $employee->resortAdmin()->first();

                // Fetch the shift for this date (Assume shift is assigned daily)
                $shift = $shifts->first(); // Assuming a default shift or a shift selection mechanism
                $gender = $employee->resortAdmin->gender;
                $religion = $employee->religion;
                $rank = $employee->rank;
                $ConfigRanks = config('settings.eligibilty');

                if ($rank == 1 || $rank == 3 || $rank == 7 || $rank == 8) {
                    $emp_grade = "1";
                } elseif ($rank == 4) {
                    $emp_grade = "4";
                } elseif ($rank == 2) {
                    $emp_grade = "2";
                } elseif ($rank == 5) {
                    $emp_grade = "5";
                } else {
                    $emp_grade = "6";
                }


                $leave_categories = ResortBenifitGridChild::join('leave_categories as lc', 'lc.id', '=', 'resort_benefit_grid_child.leave_cat_id')
                    ->leftJoin('employees_leaves as el', 'el.leave_category_id', '=', 'lc.id')
                    ->where('resort_benefit_grid_child.rank', $emp_grade)
                    ->where('lc.resort_id', $this->resort->resort_id)
                    ->where(function ($query) use ($religion, $gender) {
                        $query->where('resort_benefit_grid_child.eligible_emp_type', $gender)
                            ->orWhere('resort_benefit_grid_child.eligible_emp_type', 'all');
                        if ($religion == 'muslim') {
                            $query->orWhere('resort_benefit_grid_child.eligible_emp_type', $religion);
                        }
                        if ($religion == "") {
                            $query->Where('resort_benefit_grid_child.eligible_emp_type', 'all');
                        }
                    })
                    ->pluck('leave_type')->toArray(); // Use pluck to get leave types

                // Ensure leave categories are set
                $leave_types = implode(',', $leave_categories);

                $result->push([
                    'date' => "'$date",
                    'emp_id' => $employee->Emp_id,
                    'name' => $resortAdmin ? $resortAdmin->first_name . " " . $resortAdmin->last_name : 'No Name',
                    'shift' => '',  // Add shift name
                    'check_in_time' => null,
                    'check_out_time' => null,
                    'overtime' => null,
                    'Status' => null,
                    'Rank'=>isset($ConfigRanks[$emp_grade]) ? $ConfigRanks[$emp_grade] :null,
                    // 'LeaveType' =>null,  // Add leave types to result
                ]);
            }
        }

        return $result;
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Fetch shift names
                $shiftList = ShiftSettings::where('resort_id', $this->resort->resort_id)
                    ->pluck('ShiftName')
                    ->toArray();

                if (!empty($shiftList)) {
                    $highestRow = $sheet->getHighestRow();

                    // Create dropdown validation for column D (Shift)
                    $validation = $sheet->getDataValidation('D2:D' . $highestRow);
                    $validation->setType(DataValidation::TYPE_LIST)
                        ->setErrorStyle(DataValidation::STYLE_STOP)
                        ->setAllowBlank(true)
                        ->setShowInputMessage(true)
                        ->setShowErrorMessage(true)
                        ->setShowDropDown(true)
                        ->setErrorTitle('Input Error')
                        ->setError('Select a shift from the list')
                        ->setPromptTitle('Shift Selection')
                        ->setPrompt('Choose a shift from the dropdown')
                        ->setFormula1('"' . implode(',', $shiftList) . '"');
                }

                // Create dropdown validation for column H (Status)
                $statusList = ['DayOff', 'Present', 'Absent'];

                if (!empty($statusList)) {
                    $highestRow = $sheet->getHighestRow();

                    // Create dropdown validation for column H (Status)
                    $validation = $sheet->getDataValidation('H2:H' . $highestRow);
                    $validation->setType(DataValidation::TYPE_LIST)
                        ->setErrorStyle(DataValidation::STYLE_STOP)
                        ->setAllowBlank(true)
                        ->setShowInputMessage(true)
                        ->setShowErrorMessage(true)
                        ->setShowDropDown(true)
                        ->setErrorTitle('Input Error')
                        ->setError('Select a status from the list')
                        ->setPromptTitle('Status Selection')
                        ->setPrompt('Choose a status from the dropdown')
                        ->setFormula1('"' . implode(',', $statusList) . '"');
                }

                // Create dropdown validation for column J (LeaveType)
                // Assuming column J is where the LeaveType should be placed
                $leaveTypesList = [];
                $employees = Employee::where('resort_id', $this->resort->resort_id)->get();
                foreach ($employees as $employee) {
                    $leave_categories = ResortBenifitGridChild::join('leave_categories as lc', 'lc.id', '=', 'resort_benefit_grid_child.leave_cat_id')
                        ->where('resort_benefit_grid_child.rank', $employee->rank)
                        ->whereNotIn('lc.leave_type',['DayOff', 'Present', 'Absent'])
                        ->pluck('leave_type')

                        ->toArray();
                    $leaveTypesList = array_merge($leaveTypesList, $leave_categories);
                }
                $leaveTypesList = array_unique($leaveTypesList); // Ensure unique leave types

                if (!empty($leaveTypesList)) {
                    $highestRow = $sheet->getHighestRow();

                    // Create dropdown validation for column J (LeaveType)
                    $validation = $sheet->getDataValidation('J2:J' . $highestRow);
                    $validation->setType(DataValidation::TYPE_LIST)
                        ->setErrorStyle(DataValidation::STYLE_STOP)
                        ->setAllowBlank(true)
                        ->setShowInputMessage(true)
                        ->setShowErrorMessage(true)
                        ->setShowDropDown(true)
                        ->setErrorTitle('Input Error')
                        ->setError('Select a leave type from the list')
                        ->setPromptTitle('Leave Type Selection')
                        ->setPrompt('Choose a leave type from the dropdown')
                        ->setFormula1('"' . implode(',', $leaveTypesList) . '"');
                }
            }
        ];
    }


    // public function registerEvents(): array
    // {
    //     return [
    //         AfterSheet::class => function(AfterSheet $event) {
    //             $sheet = $event->sheet->getDelegate();

    //             // Fetch shift names
    //             $shiftList = ShiftSettings::where('resort_id', $this->resort->resort_id)
    //                 ->pluck('ShiftName')
    //                 ->toArray();

    //             if (!empty($shiftList)) {
    //                 $highestRow = $sheet->getHighestRow();

    //                 // Create dropdown validation for column A (first column)
    //                 $validation = $sheet->getDataValidation('D2:D' . $highestRow);
    //                 $validation->setType(DataValidation::TYPE_LIST)
    //                     ->setErrorStyle(DataValidation::STYLE_STOP)
    //                     ->setAllowBlank(true)
    //                     ->setShowInputMessage(true)
    //                     ->setShowErrorMessage(true)
    //                     ->setShowDropDown(true)
    //                     ->setErrorTitle('Input Error')
    //                     ->setError('Select a shift from the list')
    //                     ->setPromptTitle('Shift Selection')
    //                     ->setPrompt('Choose a shift from the dropdown')
    //                     ->setFormula1('"' . implode(',', $shiftList) . '"');
    //             }
    //             $statusList = ['DayOff', 'Present', 'Absent'];

    //             if (!empty($statusList)) {
    //                 $highestRow = $sheet->getHighestRow();

    //                 // Create dropdown validation for column I (Status)
    //                 $validation = $sheet->getDataValidation('I2:I' . $highestRow);
    //                 $validation->setType(DataValidation::TYPE_LIST)
    //                     ->setErrorStyle(DataValidation::STYLE_STOP)
    //                     ->setAllowBlank(true)
    //                     ->setShowInputMessage(true)
    //                     ->setShowErrorMessage(true)
    //                     ->setShowDropDown(true)
    //                     ->setErrorTitle('Input Error')
    //                     ->setError('Select a status from the list')
    //                     ->setPromptTitle('Status Selection')
    //                     ->setPrompt('Choose a status from the dropdown')
    //                     ->setFormula1('"' . implode(',', $statusList) . '"');
    //             }
    //         }
    //     ];
    // }
}
