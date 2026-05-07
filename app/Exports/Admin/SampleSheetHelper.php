<?php

namespace App\Exports\Admin;

use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SampleSheetHelper
{
    /**
     * Attach an Excel "List" data-validation dropdown to every cell from row 2
     * down to $rows on the given $column letter, populated with $options.
     *
     * Excel limits an inline list to ~255 chars total. If the joined options
     * exceed that, fall back to a hidden helper sheet referenced by formula.
     */
    public static function attachDropdown(Worksheet $sheet, string $column, array $options, string $promptTitle = '', int $rows = 200): void
    {
        $options = array_values(array_filter(array_map('strval', $options), 'strlen'));
        if (empty($options)) return;

        // Inline list limit: Excel rejects formula1 strings longer than ~255 chars.
        $joined = implode(',', $options);
        $useInline = strlen($joined) <= 240;

        if ($useInline) {
            $formula = '"' . str_replace('"', '""', $joined) . '"';
        } else {
            // Drop options into a hidden sheet so the dropdown can reference
            // them by range, sidestepping the inline-string length limit.
            $spreadsheet = $sheet->getParent();
            $helper = $spreadsheet->getSheetByName('_options_' . $column)
                ?? $spreadsheet->createSheet()->setTitle('_options_' . $column);
            $helper->fromArray(array_map(fn ($v) => [$v], $options), null, 'A1');
            $helper->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
            $endRow = count($options);
            $formula = '=' . $helper->getTitle() . '!$A$1:$A$' . $endRow;
        }

        for ($r = 2; $r <= $rows; $r++) {
            $cell = $sheet->getCell($column . $r);
            $v = $cell->getDataValidation();
            $v->setType(DataValidation::TYPE_LIST);
            $v->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $v->setAllowBlank(true);
            $v->setShowInputMessage(true);
            $v->setShowErrorMessage(true);
            $v->setShowDropDown(true);
            $v->setErrorTitle('Invalid value');
            $v->setError('Pick a value from the dropdown.');
            $v->setPromptTitle($promptTitle ?: 'Pick from list');
            $v->setPrompt('Click the arrow on the right of the cell to choose a valid value.');
            $v->setFormula1($formula);
        }
    }
}

