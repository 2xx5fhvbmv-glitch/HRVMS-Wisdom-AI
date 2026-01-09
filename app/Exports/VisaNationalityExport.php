<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;

class VisaNationalityExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new VisaNationalityFormSheet(),         // your main form
            new NationalitiesHiddenSheet(),       // your dropdown source
        ];
    }
}
