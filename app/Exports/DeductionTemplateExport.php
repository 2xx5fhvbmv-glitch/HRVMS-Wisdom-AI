<?php

namespace App\Exports;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use DB;
use Auth;
use Common;

class DeductionTemplateExport implements FromCollection, WithHeadings, WithEvents
{
    public function headings(): array
    {
        return [
            'Deduction Name',
            'Deduction Type',
            'Currency',
            'Maximum Limit in (%)'
        ];
    }

    public function collection()
    {
        // Map employees to date range with shift info
        $result = collect();
                
        $result->push([
            // 'emp_name' => '',
            'deduction_name' => '',
            'deduction_type' => '',
            'currency' => '',
            'maximum_limit' => ''
        ]);
            
        return $result;
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                // Create dropdown validation for column C (Currency)
                $currency = ['USD', 'Rufiyaa'];
                if (!empty($currency)) {
                    $highestRow = $sheet->getHighestRow();
                    // dd($highestRow);
                    // Create dropdown validation for column C (Status)
                    $validation = $sheet->getDataValidation('C2:C200');
                    $validation->setType(DataValidation::TYPE_LIST)
                        ->setErrorStyle(DataValidation::STYLE_STOP)
                        ->setAllowBlank(true)
                        ->setShowInputMessage(true)
                        ->setShowErrorMessage(true)
                        ->setShowDropDown(true)
                        ->setErrorTitle('Input Error')
                        ->setError('Select a currency from the list')
                        ->setPromptTitle('Status currency')
                        ->setPrompt('Choose a currency from the dropdown')
                        ->setFormula1('"' . implode(',', $currency) . '"');
                }
            }
        ];
    }
}
