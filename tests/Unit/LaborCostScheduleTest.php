<?php

namespace Tests\Unit;

use App\Services\Reports\LaborCostSchedule;
use Tests\TestCase;

class LaborCostScheduleTest extends TestCase
{
    private function rows(): array
    {
        $r = fn($d, $p, $g, $hc, $b) => ['Department' => $d, 'Position' => $p, 'Grade' => $g, 'Approved Headcount' => $hc, 'Planned Headcount' => $hc, 'Total Budget' => $b];
        return [
            $r('Front Office', 'Receptionist', 'Line Worker', 2, '$24,000.00'),
            $r('Housekeeping', 'Gardener', 'Line Worker', 1, 'MVR 15,420.00'),
            $r('Food and Beverage Service', 'Waiter', 'Line Worker', 3, '$30,000.00'),
            $r('Human Resources', 'HR Manager', 'HOD', 1, '$60,000.00'),
            $r('Spa', 'Therapist', 'Line Worker', 2, '$20,000.00'),
            $r('Total', '', '', 9, '$134,000.00'), // the Annual Workforce Plan's own total row: must not be counted again
        ];
    }

    public function test_departments_are_classified_by_keyword_for_any_resort(): void
    {
        $s = LaborCostSchedule::build($this->rows(), 15.42);

        $this->assertSame([LaborCostSchedule::OPS, LaborCostSchedule::UND, LaborCostSchedule::OTH], $s['groups']);
        $this->assertSame(['Rooms', 'Food & Beverage'], array_keys($s['tree'][LaborCostSchedule::OPS]));
        $this->assertSame(['Administrative & General', 'Property Operation & Maintenance'], array_keys($s['tree'][LaborCostSchedule::UND]));
        $this->assertArrayHasKey('Spa', $s['tree'][LaborCostSchedule::OTH]);
        // the gardener moves out of Housekeeping into grounds maintenance
        $this->assertArrayHasKey('Grounds (from Housekeeping)', $s['tree'][LaborCostSchedule::UND]['Property Operation & Maintenance']['subs']);
    }

    public function test_mvr_is_divided_by_the_rate_and_management_is_split(): void
    {
        $s = LaborCostSchedule::build($this->rows(), 15.42);

        $this->assertEqualsWithDelta(24000 + 1000 + 30000 + 60000 + 20000, $s['grand']['t']['USD'], 0.001); // 15,420 MVR ÷ 15.42 = $1,000
        $this->assertSame(0, $s['grand']['t']['MVR']);
        $this->assertSame(9, $s['grand']['hc']);
        $this->assertSame(1, $s['grand']['mh']);
        $this->assertEqualsWithDelta(60000, $s['grand']['m']['USD'], 0.001);
    }

    public function test_without_a_rate_currencies_are_never_added_together(): void
    {
        $s = LaborCostSchedule::build($this->rows(), null);

        $this->assertEqualsWithDelta(134000, $s['grand']['t']['USD'], 0.001);
        $this->assertEqualsWithDelta(15420, $s['grand']['t']['MVR'], 0.001);
    }

    public function test_the_pdf_layout_comes_from_the_caller_not_the_report_name(): void
    {
        $rows = $this->rows();
        $base = ['report' => (object) ['name' => 'Annual Workforce Plan'], 'data' => $rows, 'columns' => array_keys($rows[0]), 'insightsHtml' => ''];

        $this->assertStringContainsString('Schedule of Labor Costs', view('resorts.reports.pdf', $base + ['layout' => 'labor_cost_schedule', 'fxRate' => 15.42])->render());
        // same name and columns, no layout given (e.g. a saved report called "Annual Workforce Plan"): generic document
        $this->assertStringNotContainsString('Schedule of Labor Costs', view('resorts.reports.pdf', $base)->render());
        // an unknown layout never reaches @include
        $this->assertStringNotContainsString('Schedule of Labor Costs', view('resorts.reports.pdf', $base + ['layout' => '../../errors/404'])->render());
    }
}
