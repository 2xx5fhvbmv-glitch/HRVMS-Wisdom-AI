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

class ProductTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function headings(): array
    {
        return [
            'Name',
            'Price',
        ];
    }

    public function collection()
    {
        // Map employees to date range with shift info
            
        return new Collection([
            ['Product1', '30'],
            ['Product2', '50'],
            ['Product3', '24'],
        ]);
            
       
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Apply bold styling to the header row
                $sheet->getStyle('A1:B1')->getFont()->setBold(true);

                // Set column widths for better visibility
                $sheet->getColumnDimension('A')->setWidth(15);
                $sheet->getColumnDimension('B')->setWidth(10);
            },
        ];
    }
}