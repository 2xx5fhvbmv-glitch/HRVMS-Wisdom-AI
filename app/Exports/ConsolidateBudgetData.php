<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ConsolidateBudgetData implements WithMultipleSheets
{
    public function __construct(protected int $resortId)
    {
    }

    public function sheets(): array
    {
        return [
            new ConsolidateBudgetMainSheet($this->resortId),
            new ConsolidateBudgetDropdownSheet($this->resortId),
        ];
    }
}