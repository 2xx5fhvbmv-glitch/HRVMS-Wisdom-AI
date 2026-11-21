<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** Open siblings found while re-checking the run-3 audit leads (2026-11-21). */
class SecurityRound3OpenSiblingsTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_only_the_assigned_department_or_hr_can_answer_an_exit_clearance_form()
    {
        $worker = $this->emp('employee');
        $other = Employee::where('resort_id', $this->resortId)->where('Dept_id', '!=', $worker->Dept_id)->whereNotNull('Dept_id')->firstOrFail();
        $formId = DB::table('exit_clearance_form')->insertGetId(['resort_id' => $this->resortId, 'form_name' => 'IT clearance', 'form_type' => 'department',
            'form_structure' => '[]', 'created_at' => now(), 'updated_at' => now()]);
        $assignment = DB::table('exit_clearance_form_assignments')->insertGetId(['resort_id' => $this->resortId, 'emp_resignation_id' => 0, 'form_id' => $formId,
            'assigned_to_type' => 'department', 'department_id' => $other->Dept_id, 'status' => 'Pending', 'created_at' => now(), 'updated_at' => now()]);

        // a real line worker has no Edit on exit clearance (the demo seed grants every position everything)
        DB::table('resort_interal_pages_permissions')->where('resort_id', $this->resortId)
            ->where('Dept_id', $worker->Dept_id)->where('position_id', $worker->Position_id)->delete();
        // a line worker from another department can't sign it off
        $this->as('employee')->postJson('/resort/people/exit-clearance/department-response-store', ['exit_clearance_assignment_id' => $assignment])
            ->assertStatus(403);
        $this->assertSame('Pending', DB::table('exit_clearance_form_assignments')->where('id', $assignment)->value('status'));
    }

    public function test_only_hr_or_gm_can_send_manning_reminders()
    {
        $this->as('employee')->postJson('/resort/workforce-planning/reminder/request-manning', ['ManningReminderRequest' => 'hi'])
            ->assertStatus(403);
    }
}
