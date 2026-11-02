<?php

namespace Tests\Feature;

use App\Services\Wisdom\WisdomTools;
use Tests\TestCase;

/** GM tier sees no payroll / compensation data through Wisdom AI (founder, 2026-11-02), salary increments and loans included. */
class WisdomPayrollToolsTest extends TestCase
{
    private function names(array $ctx): array
    {
        return array_map(fn ($t) => $t['function']['name'], WisdomTools::definitions($ctx));
    }

    public function test_gm_tier_is_neither_offered_nor_allowed_payroll_tools()
    {
        $gm = ['resort_id' => 1, 'can_db' => true, 'can_payroll' => false];
        $this->assertSame([], array_values(array_intersect($this->names($gm), WisdomTools::PAYROLL_TOOLS)));
        foreach (['get_salary_increments', 'get_employee_loans'] as $tool) {
            $this->assertContains($tool, WisdomTools::PAYROLL_TOOLS);
            $this->assertStringContainsString('Access denied', WisdomTools::execute($tool, [], $gm)['error'] ?? '');
        }
        $this->assertContains('get_pending_approvals', $this->names($gm), 'counts only, still available');
    }

    public function test_full_tier_keeps_them()
    {
        $names = $this->names(['resort_id' => 1, 'can_db' => true, 'can_payroll' => true]);
        foreach (['get_salary_increments', 'get_employee_loans', 'get_payroll_summary'] as $tool) {
            $this->assertContains($tool, $names);
        }
    }
}
