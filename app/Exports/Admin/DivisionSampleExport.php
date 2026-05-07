<?php

namespace App\Exports\Admin;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DivisionSampleExport implements FromArray, WithHeadings, WithTitle
{
    public function title(): string { return 'Divisions'; }

    public function headings(): array
    {
        return ['Name', 'Code', 'Short Name'];
    }

    public function array(): array
    {
        // Two example rows so users see the expected shape.
        return [
            ['Operations',          'OPS', 'Ops'],
            ['Finance & Accounts',  'FIN', 'F&A'],
        ];
    }
}
