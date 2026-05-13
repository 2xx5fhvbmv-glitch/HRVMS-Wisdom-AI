<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class NationalitiesHiddenSheet implements FromArray, WithTitle, WithEvents
{
    public function array(): array
    {
        $list = array_values(config('settings.countries'));
        return array_map(function ($country) {
            return [$country];
        }, $list);
    }

    public function title(): string
    {
        return 'Nationalities';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

                $spreadsheet = $sheet->getParent();
                $spreadsheet->addNamedRange(
                    new \PhpOffice\PhpSpreadsheet\NamedRange(
                        'DropdownList',
                        $sheet,
                        'A1:A' . count(config('settings.countries'))
                    )
                );
            },
        ];
    }
}
