<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ConsolidateBudgetData implements WithMultipleSheets, Export
{
    public function __construct(protected array $exportData)
    {
    }

    public function sheets(): array
    {
        $mvrToDollarRate = $this->exportData['mvrToDollarRate'];

        $sheets = [];
        foreach ($this->exportData['sheets'] as $title => $sheet) {
            $sheets[] = new ConsolidateBudgetMainSheet($title, $sheet['tree'], $sheet['resortCosts'], $mvrToDollarRate);
        }

        return $sheets;
    }
}
