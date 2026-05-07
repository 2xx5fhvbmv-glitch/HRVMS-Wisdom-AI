<?php

namespace App\Exports\Admin;

use App\Models\Department;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class PositionSampleExport implements FromArray, WithHeadings, WithTitle, WithEvents
{
    public function title(): string { return 'Positions'; }

    public function headings(): array
    {
        return ['Department Name', 'Position Title', 'Code', 'Short Title'];
    }

    public function array(): array
    {
        $exampleDept = Department::where('status', 'active')->orderBy('id')->value('name') ?: 'Front Office';
        return [
            [$exampleDept, 'Front Office Manager', 'FOM',  'FO Mgr'],
            [$exampleDept, 'Receptionist',         'RECP', 'Recp'],
        ];
    }

    public function registerEvents(): array
    {
        $departments = Department::where('status', 'active')->orderBy('name')->pluck('name')->all();
        return [
            AfterSheet::class => function (AfterSheet $event) use ($departments) {
                SampleSheetHelper::attachDropdown($event->sheet->getDelegate(), 'A', $departments, 'Pick from the existing Departments');
            },
        ];
    }
}
