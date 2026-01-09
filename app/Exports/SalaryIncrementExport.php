<?php

namespace App\Exports;

use App\Models\PeopleSalaryIncrement;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Helpers\Common;

class SalaryIncrementExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * Fetch the data to be exported.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return PeopleSalaryIncrement::where('resort_id',auth()->guard('resort-admin')->user()->resort_id)->with([
            'employee.resortAdmin:id,first_name,last_name',
            'employee.department:id,name',
            'employee.position:id,position_title',
            'peopleSalaryIncrementStatusFinance',
            'peopleSalaryIncrementStatusGM'
        ])
        ->whereHas('employee', function ($q) {
            $q->where('resort_id', auth()->guard('resort-admin')->user()->resort_id);
        })
        ->get();
    }

    public function map($row): array
    {
        $dateFormat = Common::getDateFormateFromSettings();
        return [
            $row->employee->Emp_id ?? '-',
            optional($row->employee->resortAdmin)->full_name ?? '-',
            optional($row->employee->position)->position_title ?? '-',
            optional($row->employee->department)->name ?? '-',
            number_format($row->previous_salary, 2),
            number_format($row->new_salary, 2),
            number_format($row->increment_amount, 2),
            $row->increment_type,
            $row->effective_date ? \Carbon\Carbon::parse($row->effective_date)->format($dateFormat) : '-',
            $row->remarks ?? '-',
            $row->status,
            optional($row->peopleSalaryIncrementStatusFinance)->status ?? '-',
            optional($row->peopleSalaryIncrementStatusFinance)->remarks ?? '-',
            optional($row->peopleSalaryIncrementStatusGM)->status ?? '-',
            optional($row->peopleSalaryIncrementStatusGM)->remarks ?? '-',

            optional($row->employee)->last_increment_salary_amount ?? '-',
            optional($row->employee)->last_salary_increment_type ?? '-',
            optional($row->employee)->incremented_date ?? '-',
        ];
    }

    
    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Position',
            'Department',
            'Current Salary',
            'New Salary',
            'Increment Amount',
            'Increment Type',
            'Effective Date',
            'Remarks',
            'Status',
            'Finance Status',
            'Finance Remark',
            'GM Status',
            'GM Remark',
            'Last Increment Salary Amount',
            'Last Increment Type',
            'Last Increment Date',
        ];
    }
}