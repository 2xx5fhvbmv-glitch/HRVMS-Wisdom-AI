<?php

namespace Tests\Feature;

use App\Helpers\Common;
use App\Models\Employee;
use Tests\TestCase;

// Read-only: runs against whatever data the test DB has (L-06).
class IslandPassApprovalChainTest extends TestCase
{
    public function test_applicant_is_never_their_own_approver_and_order_is_sm_hr_hod(): void
    {
        $resortId = Employee::where('status', 'Active')->value('resort_id');
        if (!$resortId) {
            $this->markTestSkipped('No employees in test DB.');
        }
        $hrIds = Common::getResortHrEmployeeIds($resortId);
        $applicants = Employee::where('resort_id', $resortId)->where('status', 'Active')
            ->where(fn($q) => $q->whereIn('id', $hrIds)->orWhereIn('rank', [1, 2]))->limit(10)->get();

        foreach ($applicants as $a) {
            $chain = Common::buildIslandPassApprovalChain($resortId, $a);
            $this->assertNotContains((int) $a->id, $chain['flow']->pluck('id')->map(fn($i) => (int) $i)->all());
            // insertion order SM, HR, HOD => executed HOD -> HR -> SM (highest id first)
            $roles = $chain['flow']->pluck('approver_role')->values()->all();
            $this->assertSame(array_values(array_intersect(['SM', 'HR', 'HOD'], $roles)), $roles);
        }
        $this->assertTrue(true);
    }
}
