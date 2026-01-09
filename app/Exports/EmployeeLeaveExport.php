<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\LeaveCategory;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterSheet;

// Main Export Class
class EmployeeLeaveExport implements WithMultipleSheets
{
    use Exportable;

    public $resort_id, $division, $department, $section, $position;

    public function __construct($resort_id, $division, $department, $section, $position)
    {
        $this->resort_id = $resort_id;
        $this->division = $division;
        $this->department = $department;
        $this->section = $section;
        $this->position = $position;
    }

    public function sheets(): array
    {
        return [
            'LeaveData' => new LeaveSheet($this->resort_id, $this->division, $this->department, $this->section, $this->position),
        ];
    }
}