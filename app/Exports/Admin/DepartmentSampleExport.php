<?php

namespace App\Exports\Admin;

use App\Models\Division;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Sample sheet for Department bulk upload.
 *
 * Column A is "Division Name" — we attach an Excel data validation list to
 * that column with all currently-active divisions, so the user picks a real
 * value instead of free-typing a misspelt one.
 */
class DepartmentSampleExport implements FromArray, WithHeadings, WithTitle, WithEvents
{
    public function title(): string { return 'Departments'; }

    public function headings(): array
    {
        return ['Division Name', 'Name', 'Code', 'Short Name'];
    }

    public function array(): array
    {
        $exampleDiv = Division::where('status', 'active')->orderBy('id')->value('name') ?: 'Operations';
        return [
            [$exampleDiv, 'Front Office',  'FO', 'Front'],
            [$exampleDiv, 'Housekeeping',  'HK', 'HK'],
        ];
    }

    public function registerEvents(): array
    {
        $divisions = Division::where('status', 'active')->orderBy('name')->pluck('name')->all();
        return [
            AfterSheet::class => function (AfterSheet $event) use ($divisions) {
                SampleSheetHelper::attachDropdown($event->sheet->getDelegate(), 'A', $divisions, 'Pick from the existing Divisions');
            },
        ];
    }
}
