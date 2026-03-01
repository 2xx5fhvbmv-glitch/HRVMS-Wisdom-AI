<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Collection;

class ProductTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function headings(): array
    {
        return [
            'Name',
            'Price',
            'Currency',
        ];
    }

    public function collection()
    {
        return new Collection([
            ['Product1', '30', 'Dollar'],
            ['Product2', '50', 'MVR'],
            ['Product3', '24', 'Dollar'],
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
                $sheet->getColumnDimension('A')->setWidth(20);
                $sheet->getColumnDimension('B')->setWidth(12);
                $sheet->getColumnDimension('C')->setWidth(12);

                // Currency dropdown (Dollar, MVR) - list validation only; no sheet protection so file stays editable
                $currencyColumn = 'C';
                $validation = $sheet->getCell($currencyColumn . '2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setErrorTitle('Invalid Currency');
                $validation->setError('Only "Dollar" or "MVR" are allowed. Use the dropdown to select.');
                $validation->setPromptTitle('Currency');
                $validation->setPrompt('Select Dollar or MVR from the dropdown.');
                $validation->setFormula1('"Dollar,MVR"');
                $validation->setShowDropDown(true);

                for ($row = 2; $row <= 500; $row++) {
                    $sheet->getCell($currencyColumn . $row)->setDataValidation(clone $validation);
                }
            },
        ];
    }
}