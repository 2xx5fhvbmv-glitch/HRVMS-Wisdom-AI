<?php
namespace App\Exports;

use App\Models\EmployeePromotion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PromotionHistoryExport implements FromCollection, WithHeadings
{
    protected $resortid;

    public function collection()
    {
        return EmployeePromotion::with(['employee.position',
            'employee.department',
            'employee.resortAdmin',
            'currentPosition', 
            'newPosition',
            'approvals',
            'createdBy.GetEmployee', 
            'modifiedBy'])->where('resort_id',$this->resortid)->where('status','Approved')->get()->map(function ($p) {
            return [
                $p->employee->Emp_id,
                $p->employee->resortAdmin->full_name,
                $p->effective_date,
                $p->currentPosition->position_title ?? 'N/A',
                $p->newPosition->position_title ?? 'N/A',
                $p->old_salary,
                $p->new_salary,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Employee ID',
            'Employee Name',
            'Effective Date',
            'Old Position',
            'New Position',
            'Old Salary',
            'New Salary',
        ];
    }
}
