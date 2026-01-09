<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\ResortBenifitGridChild;
use App\Models\LeaveCategory;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Collection;
use DB;
use Auth;
use Common;

class ServiceChargeExport implements FromCollection, WithHeadings, WithEvents
{
    public function headings(): array
    {
        return [
            'Month',
            'Year',
            'Service Charge (in $)',
        ];
    }

    public function collection()
    {
        // Provide a sample empty row for user reference
        return new Collection([
            ['January', '2025', ''],
            ['February', '2025', ''],
            ['March', '2025', ''],
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Apply bold styling to the header row
                $sheet->getStyle('A1:C1')->getFont()->setBold(true);

                // Set column widths for better visibility
                $sheet->getColumnDimension('A')->setWidth(15);
                $sheet->getColumnDimension('B')->setWidth(10);
                $sheet->getColumnDimension('C')->setWidth(20);
            },
        ];
    }
}