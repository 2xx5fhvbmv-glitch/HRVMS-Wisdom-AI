<?php
namespace App\Exports;

use App\Models\ShiftSettings;
use App\Models\ParentAttendace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use App\Models\Employee;
use App\Models\LeaveCategory;
use App\Models\ResortBenifitGridChild;
use Carbon\Carbon;

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
        return ['Date', 'Employee ID', 'Name', 'Shift', 'Check-In Time', 'Check-Out Time', 'Overtime', 'Status', 'Rank', 'LeaveType'];
    }

    /** Parse date from request (supports d/m/Y and d-m-Y) */
    protected function parseDate($dateStr)
    {
        if (empty($dateStr)) {
            return null;
        }
        $dateStr = trim($dateStr);
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y'] as $format) {
            try {
                $d = Carbon::createFromFormat($format, $dateStr);
                if ($d) {
                    return $d;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        try {
            return Carbon::parse($dateStr);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function collection()
    {
        $startCarbon = $this->parseDate($this->startDate);
        $endCarbon = $this->parseDate($this->endDate);
        if (!$startCarbon || !$endCarbon || $startCarbon->gt($endCarbon)) {
            return collect();
        }

        $startStr = $startCarbon->format('Y-m-d');
        $endStr = $endCarbon->format('Y-m-d');

        $employees = Employee::with('resortAdmin')
            ->where('resort_id', $this->resort->resort_id)
            ->get();

        // Fetch actual attendance for date range: Emp_id, date, CheckingTime, CheckingOutTime, Status, OverTime, ShiftName
        $attendanceRows = DB::table('parent_attendaces as pa')
            ->leftJoin('shift_settings as ss', 'ss.id', '=', 'pa.Shift_id')
            ->where('pa.resort_id', $this->resort->resort_id)
            ->whereBetween('pa.date', [$startStr, $endStr])
            ->select(
                'pa.Emp_id',
                'pa.date',
                'pa.CheckingTime as check_in',
                'pa.CheckingOutTime as check_out',
                'pa.Status as status',
                'pa.OverTime as overtime',
                DB::raw('ss.ShiftName as shift_name')
            )
            ->get();

        $attendanceByKey = [];
        foreach ($attendanceRows as $row) {
            $dateFormatted = Carbon::parse($row->date)->format('Y-m-d');
            $key = $row->Emp_id . '|' . $dateFormatted;
            if (!isset($attendanceByKey[$key])) {
                $attendanceByKey[$key] = $row;
            } else {
                $attendanceByKey[$key]->check_in = $attendanceByKey[$key]->check_in ?: $row->check_in;
                $attendanceByKey[$key]->check_out = $attendanceByKey[$key]->check_out ?: $row->check_out;
                $attendanceByKey[$key]->overtime = $attendanceByKey[$key]->overtime ?: $row->overtime;
                $attendanceByKey[$key]->status = $attendanceByKey[$key]->status ?: $row->status;
                $attendanceByKey[$key]->shift_name = $attendanceByKey[$key]->shift_name ?: $row->shift_name;
            }
        }

        $dates = collect();
        $currentDate = $startCarbon->copy();
        while ($currentDate->lte($endCarbon)) {
            $dates->push($currentDate->format('Y-m-d'));
            $currentDate->addDay();
        }

        $result = collect();
        $ConfigRanks = config('settings.eligibilty', []);

        foreach ($dates as $dateYmd) {
            $dateDisplay = Carbon::parse($dateYmd)->format('d-m-Y');
            foreach ($employees as $employee) {
                $resortAdmin = $employee->resortAdmin()->first();
                $empName = $resortAdmin ? trim($resortAdmin->first_name . ' ' . $resortAdmin->last_name) : 'No Name';

                $key = $employee->id . '|' . $dateYmd;
                $att = $attendanceByKey[$key] ?? null;

                $shiftName = $att->shift_name ?? '';
                $checkIn = $att->check_in ?? '';
                $checkOut = $att->check_out ?? '';
                $overtime = $att->overtime ?? '';
                $status = $att->status ?? '';

                $gender = $resortAdmin ? ($resortAdmin->gender ?? null) : null;
                $religion = $employee->religion ?? '';
                $rank = $employee->rank;
                if ($rank == 1 || $rank == 3 || $rank == 7 || $rank == 8) {
                    $emp_grade = '1';
                } elseif ($rank == 4) {
                    $emp_grade = '4';
                } elseif ($rank == 2) {
                    $emp_grade = '2';
                } elseif ($rank == 5) {
                    $emp_grade = '5';
                } else {
                    $emp_grade = '6';
                }

                $leave_categories = ResortBenifitGridChild::join('leave_categories as lc', 'lc.id', '=', 'resort_benefit_grid_child.leave_cat_id')
                    ->where('resort_benefit_grid_child.rank', $emp_grade)
                    ->where('lc.resort_id', $this->resort->resort_id)
                    ->where(function ($query) use ($religion, $gender) {
                        $query->where('resort_benefit_grid_child.eligible_emp_type', $gender)
                            ->orWhere('resort_benefit_grid_child.eligible_emp_type', 'all');
                        if ($religion == 'muslim') {
                            $query->orWhere('resort_benefit_grid_child.eligible_emp_type', $religion);
                        }
                        if ($religion == '') {
                            $query->where('resort_benefit_grid_child.eligible_emp_type', 'all');
                        }
                    })
                    ->pluck('leave_type')->toArray();
                $leave_types = implode(',', $leave_categories);

                $rankLabel = isset($ConfigRanks[$emp_grade]) ? $ConfigRanks[$emp_grade] : null;

                $result->push([
                    'date' => "'" . $dateDisplay,
                    'emp_id' => $employee->Emp_id,
                    'name' => $empName,
                    'shift' => $shiftName,
                    'check_in_time' => $checkIn,
                    'check_out_time' => $checkOut,
                    'overtime' => $overtime,
                    'Status' => $status,
                    'Rank' => $rankLabel,
                    'LeaveType' => $leave_types,
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
