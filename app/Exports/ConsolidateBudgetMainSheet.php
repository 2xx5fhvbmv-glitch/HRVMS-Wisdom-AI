<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\ResortBudgetCost;

class ConsolidateBudgetMainSheet implements FromArray, WithHeadings, WithTitle, WithEvents
{
    public function __construct(protected int $resortId)
    {
    }

    public function title(): string
    {
        return 'ConsolidatedBudgetTemplate';
    }

    public function headings(): array
    {
        $tableData = ResortBudgetCost::where('resort_id', $this->resortId)
            ->get()->pluck('particulars')->toArray();

        return array_merge([
            'Division',
            'Department',
            'Position',
            'Rank',
            'NATION',
            'NoOfPosition',
            'Current Salary',
            'Proposed Salary',
        ], $tableData);
    }

    public function array(): array
    {
        // 300 empty rows for data entry
        return array_fill(0, 300, array_fill(0, count($this->headings()), ''));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet     = $event->sheet->getDelegate();
                $headings  = $this->headings();
                $lastCol   = Coordinate::stringFromColumnIndex(count($headings));

                // Bold + blue header row
                $sheet->getStyle("A1:{$lastCol}1")
                    ->getFont()->setBold(true);
                $sheet->getStyle("A1:{$lastCol}1")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('DDEBF7');

                // Auto-width for all columns
                foreach (range(1, count($headings)) as $i) {
                    $col = Coordinate::stringFromColumnIndex($i);
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}
