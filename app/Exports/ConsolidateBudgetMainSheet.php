<?php

namespace App\Exports;

use App\Helpers\Common;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ConsolidateBudgetMainSheet implements FromArray, WithHeadings, WithTitle, WithEvents
{
    private const FIXED_COLUMNS = [
        'Division', 'Department', 'Position', 'Rank', 'Employee/Vacant Name',
        'NATION', 'NoOfPosition', 'Current Salary', 'Proposed Salary', 'Allowance',
    ];

    // 0-based indices into FIXED_COLUMNS, used to compose the row Total
    // formula (see employeeRow()/vacantRow()).
    private const PROPOSED_SALARY_COL = 8;
    private const ALLOWANCE_COL = 9;

    // First 0-based column that's a plain numeric aggregate (NoOfPosition
    // onward) — everything from here through the Total column is summed
    // uniformly for subtotal/rollup rows (see subtotalRow()/rollupRow()).
    private const FIRST_NUMERIC_COL = 6;

    /** @var array<object> */
    protected array $resortCosts;

    private array $boldRows = [];
    private array $labelFillRows = [];
    private array $subtotalFillRows = [];

    public function __construct(
        protected string $title,
        protected array $tree,
        $resortCosts,
        protected float $mvrToDollarRate
    ) {
        $this->resortCosts = collect($resortCosts)->values()->all();
    }

    public function title(): string
    {
        // Excel sheet titles: max 31 chars, no \ / ? * [ ] : — none of our
        // 5 fixed titles ("Casual & Intern" etc.) hit either limit today,
        // but guard anyway since this is the only place that can crash the
        // whole export over a title string.
        return substr(str_replace(['\\', '/', '?', '*', '[', ']', ':'], '', $this->title), 0, 31);
    }

    public function headings(): array
    {
        return array_merge(
            self::FIXED_COLUMNS,
            collect($this->resortCosts)->pluck('particulars')->all(),
            ['Total']
        );
    }

    public function array(): array
    {
        $fixedCount = count(self::FIXED_COLUMNS);
        $costCount = count($this->resortCosts);
        $totalColumns = $fixedCount + $costCount + 1;
        $lastCostCol = Coordinate::stringFromColumnIndex($fixedCount + $costCount);
        $currentSalaryCol = Coordinate::stringFromColumnIndex(7 + 1); // 0-based idx 7 = Current Salary
        $proposedSalaryCol = Coordinate::stringFromColumnIndex(self::PROPOSED_SALARY_COL + 1);
        $allowanceCol = Coordinate::stringFromColumnIndex(self::ALLOWANCE_COL + 1);
        $lastNumericColIndex = $totalColumns - 1; // 0-based index of the Total column

        $rows = [];
        $row = 1; // heading row

        foreach ($this->tree as $divisionName => $divisionData) {
            $row++;
            $rows[] = $this->blankRow($totalColumns, [0 => $divisionName]);
            $this->boldRows[] = $row;
            $this->labelFillRows[] = $row;

            $departmentSubtotalRows = [];

            foreach ($divisionData['departments'] ?? [] as $departmentName => $departmentData) {
                $row++;
                $rows[] = $this->blankRow($totalColumns, [1 => $departmentName]);
                $this->boldRows[] = $row;
                $this->labelFillRows[] = $row;

                $blockFirstRow = $row + 1;

                $positions = $departmentData['positions'] ?? [];
                foreach ($departmentData['sections'] ?? [] as $sectionData) {
                    $positions = array_merge($positions, $sectionData['positions'] ?? []);
                }

                foreach ($positions as $positionName => $positionData) {
                    $isPermanent = ($positionData['employment_type'] ?? 'Permanent') === 'Permanent';

                    foreach ($positionData['employees'] ?? [] as $employee) {
                        $row++;
                        $rows[] = $this->employeeRow($positionName, $positionData, $employee, $fixedCount, $costCount, $currentSalaryCol, $proposedSalaryCol, $allowanceCol, $lastCostCol, $row);
                    }

                    $maxVacantcount = (int) ($positionData['max_counts']['max_vacantcount'] ?? 0);
                    for ($i = 1; $i <= $maxVacantcount; $i++) {
                        $vacantConfig = $positionData['vacant_configurations'][$i] ?? null;
                        $row++;
                        $rows[] = $this->vacantRow($positionName, $positionData, $vacantConfig, $isPermanent, $fixedCount, $costCount, $currentSalaryCol, $proposedSalaryCol, $allowanceCol, $lastCostCol, $row);
                    }
                }

                $blockLastRow = $row;

                if ($blockLastRow >= $blockFirstRow) {
                    $row++;
                    $rows[] = $this->subtotalRow($totalColumns, self::FIRST_NUMERIC_COL, $lastNumericColIndex, $blockFirstRow, $blockLastRow, 1, 'Subtotal');
                    $this->boldRows[] = $row;
                    $this->subtotalFillRows[] = $row;
                    $departmentSubtotalRows[] = $row;
                }
            }

            if (!empty($departmentSubtotalRows)) {
                $row++;
                $rows[] = $this->rollupRow($totalColumns, self::FIRST_NUMERIC_COL, $lastNumericColIndex, $departmentSubtotalRows, 0, 'Division Total');
                $this->boldRows[] = $row;
                $this->subtotalFillRows[] = $row;
            }
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $headings = $this->headings();
                $lastCol = Coordinate::stringFromColumnIndex(count($headings));

                $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true);
                $sheet->getStyle("A1:{$lastCol}1")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('DDEBF7');

                foreach ($this->boldRows as $r) {
                    $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFont()->setBold(true);
                }
                foreach ($this->labelFillRows as $r) {
                    $sheet->getStyle("A{$r}:{$lastCol}{$r}")
                        ->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('DDEBF7');
                }
                foreach ($this->subtotalFillRows as $r) {
                    $sheet->getStyle("A{$r}:{$lastCol}{$r}")
                        ->getFill()->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('FFF2CC');
                }

                foreach (range(1, count($headings)) as $i) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
                }
            },
        ];
    }

    /**
     * A row of blank cells with a few positions overridden — $overrides is
     * [0-based column index => value].
     */
    private function blankRow(int $totalColumns, array $overrides): array
    {
        $row = array_fill(0, $totalColumns, '');
        foreach ($overrides as $col => $value) {
            $row[$col] = $value;
        }
        return $row;
    }

    /**
     * Row Total must reconcile with the tree's own canonical
     * calculated_total (Common::calculatePositionTotal() /
     * computeEmployeeYearlyTotalBatched() / computeVacantYearlyTotalBatched()):
     * Permanent = salary leg + Allowance + cost-template columns;
     * Casual/Intern = cost-template columns only (no salary/allowance leg
     * — see computeEmployeeYearlyTotalBatched()'s own category branch).
     * Allowance is always 0 for Casual/Intern and for vacant slots, so
     * including it unconditionally in the "costs-only" branch is harmless.
     *
     * The salary leg itself falls back to Current Salary when Proposed
     * Salary is unset/0 — same rule computeEmployeeYearlyTotalBatched() /
     * computeVacantYearlyTotalBatched() use ($sharedFallback) — expressed
     * as a live IF() so editing either salary cell in Excel still
     * recalculates correctly, rather than baking the fallback into a
     * static number at export time.
     */
    private function totalFormula(bool $isPermanent, string $currentSalaryCol, string $proposedSalaryCol, string $allowanceCol, string $lastCostCol, int $rowNumber): string
    {
        if (!$isPermanent) {
            return "=SUM({$allowanceCol}{$rowNumber}:{$lastCostCol}{$rowNumber})";
        }
        $salaryLeg = "IF({$proposedSalaryCol}{$rowNumber}>0,{$proposedSalaryCol}{$rowNumber},{$currentSalaryCol}{$rowNumber})";
        return "={$salaryLeg}+SUM({$allowanceCol}{$rowNumber}:{$lastCostCol}{$rowNumber})";
    }

    private function employeeRow(string $positionName, array $positionData, $employee, int $fixedCount, int $costCount, string $currentSalaryCol, string $proposedSalaryCol, string $allowanceCol, string $lastCostCol, int $rowNumber): array
    {
        $row = array_fill(0, $fixedCount + $costCount + 1, '');
        $row[2] = $positionName;
        $row[3] = $positionData['rank'] ?? '';
        $row[4] = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
        $row[5] = $employee->nationality ?? '';
        $row[6] = 1;
        $row[7] = (float) ($employee->configured_basic_salary ?? 0);
        $row[8] = (float) ($employee->configured_current_salary ?? 0);

        $employeeCategory = Common::manningCategory($employee->employment_type ?? '');
        $isPermanent = $employeeCategory === 'Permanent';
        $row[self::ALLOWANCE_COL] = $isPermanent ? (float) ($employee->allowance_yearly ?? 0) : 0.0;

        foreach ($this->resortCosts as $idx => $cost) {
            $applies = !isset($cost->export_category) || $cost->export_category === $employeeCategory;
            $row[$fixedCount + $idx] = $applies ? (float) ($employee->cost_breakdown[$cost->id] ?? 0) : 0.0;
        }

        $row[$fixedCount + $costCount] = $this->totalFormula($isPermanent, $currentSalaryCol, $proposedSalaryCol, $allowanceCol, $lastCostCol, $rowNumber);
        return $row;
    }

    private function vacantRow(string $positionName, array $positionData, ?array $vacantConfig, bool $isPermanent, int $fixedCount, int $costCount, string $currentSalaryCol, string $proposedSalaryCol, string $allowanceCol, string $lastCostCol, int $rowNumber): array
    {
        $row = array_fill(0, $fixedCount + $costCount + 1, '');
        $row[2] = $positionName;
        $row[3] = $positionData['rank'] ?? '';
        $row[4] = 'Vacant';
        $row[5] = '';
        $row[6] = 1;
        // Per legacy ResortVacantBudgetCost mapping: basic_salary = Current,
        // current_salary = Proposed (see computeVacantYearlyTotalBatched()).
        $row[7] = (float) ($vacantConfig['vacant_budget_cost']->basic_salary ?? 0);
        $row[8] = (float) ($vacantConfig['vacant_budget_cost']->current_salary ?? 0);
        $row[self::ALLOWANCE_COL] = 0.0; // no employee on a vacant slot, no allowance leg

        $lookup = [];
        foreach ($vacantConfig['configurations'] ?? [] as $config) {
            $lookup[(int) $config->resort_budget_cost_id] = $config->currency === 'MVR'
                ? (float) $config->value * $this->mvrToDollarRate
                : (float) $config->value;
        }
        foreach ($this->resortCosts as $idx => $cost) {
            $row[$fixedCount + $idx] = $lookup[(int) $cost->id] ?? 0.0;
        }

        $row[$fixedCount + $costCount] = $this->totalFormula($isPermanent, $currentSalaryCol, $proposedSalaryCol, $allowanceCol, $lastCostCol, $rowNumber);
        return $row;
    }

    /**
     * Every numeric column from NoOfPosition through Total, summed over the
     * data-row range — including Total itself, which is why this doesn't
     * need to separately re-derive Total from salary/allowance/cost parts:
     * each data row's own Total already correctly reflects its category
     * (see totalFormula()), so summing that column is correct even for a
     * block with mixed Permanent/Casual/Intern rows (the merged sheets).
     */
    private function subtotalRow(int $totalColumns, int $firstNumericCol, int $lastNumericCol, int $firstRow, int $lastRow, int $labelCol, string $label): array
    {
        $row = array_fill(0, $totalColumns, '');
        $row[$labelCol] = $label;
        for ($i = $firstNumericCol; $i <= $lastNumericCol; $i++) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $row[$i] = "=SUM({$col}{$firstRow}:{$col}{$lastRow})";
        }
        return $row;
    }

    private function rollupRow(int $totalColumns, int $firstNumericCol, int $lastNumericCol, array $sourceRows, int $labelCol, string $label): array
    {
        $row = array_fill(0, $totalColumns, '');
        $row[$labelCol] = $label;
        for ($i = $firstNumericCol; $i <= $lastNumericCol; $i++) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $row[$i] = $this->sumRefsFormula($sourceRows, $col);
        }
        return $row;
    }

    private function sumRefsFormula(array $rowNumbers, string $colLetter): string
    {
        $refs = array_map(fn ($r) => "{$colLetter}{$r}", $rowNumbers);
        return '=SUM(' . implode(',', $refs) . ')';
    }
}
