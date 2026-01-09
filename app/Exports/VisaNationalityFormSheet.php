<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class VisaNationalityFormSheet implements FromArray, WithHeadings, WithTitle, WithEvents
{
    public function array(): array
    {
        return [
                        
        ];
    }

    public function headings(): array
    {
        return ['Nationality','Amount'];
    }

    public function title(): string
    {
        return 'VisaForm';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Apply data validation to Nationality column (column B)
                for ($row = 2; $row <= 300; $row++) {
                    $validation = $sheet->getCell('A' . $row)->getDataValidation();
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(true);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Invalid input');
                    $validation->setError('Value is not in the list.');
                    $validation->setPromptTitle('Pick from list');
                    $validation->setPrompt('Please pick a value from the dropdown');
                    $validation->setFormula1('=DropdownList'); // Named range from hidden sheet
                }
            },
        ];
    }
}
