<?php

namespace Tests\Feature;

use App\Models\JobDescriptionEmployeeRecord;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** Founder 2026-10-15: "Job Description Not Signed / Declined" flags Active employees only. */
class JobDescriptionComplianceActiveOnlyTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    public function test_inactive_employees_are_not_flagged()
    {
        $this->buildDemoOrganisation();
        $staff = $this->emp('employee');
        DB::table('job_description_employee_records')->insert(['resort_id' => $this->resortId, 'job_description_id' => DB::table('job_descriptions')->value('id'), 'employee_id' => $staff->id,
            'employer_name' => 'Demo', 'employee_full_name' => 'X', 'status' => 'Pending', 'sent_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $open = fn () => DB::table('compliances')->where('employee_id', $staff->id)->where('compliance_breached_name', 'Job Description Not Signed')->where('status', 'Breached')->count();

        $this->assertSame(['Job Description Not Signed'], JobDescriptionEmployeeRecord::syncCompliance($this->resortId, $staff->id));
        $this->assertSame(1, $open());

        DB::table('employees')->where('id', $staff->id)->update(['status' => 'Inactive']);
        $this->assertSame([], JobDescriptionEmployeeRecord::syncCompliance($this->resortId, $staff->id));
        $this->assertSame(0, $open(), 'the open breach resolves');
        $this->assertSame([], JobDescriptionEmployeeRecord::syncCompliance($this->resortId, $staff->id));
        $this->assertSame(0, $open(), 'and is not re-created');
    }
}
