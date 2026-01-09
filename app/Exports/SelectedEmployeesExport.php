<?php 
namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SelectedEmployeesExport implements FromCollection, WithHeadings
{
    protected $ids;

    public function __construct(array $ids)
    {
        $this->ids = $ids;
    }

    public function collection()
    {
        return Employee::whereIn('id', $this->ids)
            ->where('resort_id', auth()->user()->resort_id)
            ->with(['position', 'department', 'resortAdmin'])
            ->get()
            ->map(function ($emp) {
                return [
                    'ID' => $emp->Emp_id,
                    'Full Name' => $emp->resortAdmin->full_name ?? '',
                    'Position' => $emp->position->position_title ?? '',
                    'Department' => $emp->department->name ?? '',
                    'Status' => $emp->status,
                ];
            });
    }

    public function headings(): array
    {
        return ['ID', 'Full Name', 'Position', 'Department', 'Status'];
    }
}

