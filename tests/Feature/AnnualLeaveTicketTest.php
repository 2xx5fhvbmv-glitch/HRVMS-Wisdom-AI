<?php

namespace Tests\Feature;

use App\Helpers\Common;
use App\Models\Employee;
use App\Models\ResortAdmin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\TestCase;

/** Flight-ticket tracking (remaining-work list, Part E): HR's per-cycle Yes/No, editable, reset per cycle, with a report. */
class AnnualLeaveTicketTest extends TestCase
{
    use DatabaseTransactions;

    private ResortAdmin $hr;
    private Employee $emp;
    private int $leaveId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Session\Middleware\AuthenticateSession::class);
        // An HR employee login (the leave page needs an employee record) in a resort that has Annual Leave.
        $this->hr = ResortAdmin::where('status', 'Active')->whereIn('resort_id', DB::table('leave_categories')->where('leave_type', 'Annual Leave')->select('resort_id'))
            ->whereHas('GetEmployee', fn ($q) => $q->where('status', 'Active'))->get()->first(fn ($a) => Common::isHR($a->GetEmployee))
            ?? $this->markTestSkipped('No HR employee in a resort with Annual Leave.');
        $annual = DB::table('leave_categories')->where('resort_id', $this->hr->resort_id)->where('leave_type', 'Annual Leave')->value('id')
            ?? $this->markTestSkipped('No Annual Leave category.');
        $this->emp = Employee::where('resort_id', $this->hr->resort_id)->where('status', 'Active')->whereHas('resortAdmin')->firstOrFail();
        $this->emp->forceFill(['joining_date' => now()->subYears(2)->subMonths(3)->toDateString()])->save();
        $leave = (array) DB::table('employees_leaves')->where('resort_id', $this->hr->resort_id)->first() ?: $this->markTestSkipped('No leave rows.');
        unset($leave['id']);
        $this->leaveId = DB::table('employees_leaves')->insertGetId(array_merge($leave, ['emp_id' => $this->emp->id, 'leave_category_id' => $annual, 'status' => 'Pending']));
    }

    public function test_hr_answers_on_the_leave_page_can_change_it_and_a_new_cycle_starts_fresh()
    {
        $this->actingAs($this->hr, 'resort-admin');
        $this->get(route('leave.details', ['leave_id' => base64_encode($this->leaveId)]))->assertOk()
            ->assertSee('round-trip ticket for the current cycle', false)->assertSee('Not answered yet.');

        $save = fn ($v) => $this->postJson(route('leave.annualTickets.store'), ['employee_id' => $this->emp->id, 'company_provided' => $v]);
        $save(1)->assertOk()->assertJsonPath('ticket.company_provided', true);
        $cycle = Common::annualLeaveCycle($this->emp->joining_date);
        $this->assertSame(1, DB::table('employee_annual_ticket_records')->where('employee_id', $this->emp->id)->count());
        $this->assertSame($cycle['start']->toDateString(), DB::table('employee_annual_ticket_records')->where('employee_id', $this->emp->id)->value('cycle_start_date'));

        $save(0)->assertOk()->assertJsonPath('ticket.company_provided', false);   // same cycle: changed, not a second row
        $this->assertSame(1, DB::table('employee_annual_ticket_records')->where('employee_id', $this->emp->id)->count());

        $this->travelTo($cycle['end']->copy()->addDay());                        // anniversary: new cycle, fresh question
        $this->assertNull(Common::annualTicketState($this->emp->fresh())['company_provided']);
        $save(1)->assertOk();
        $this->assertSame(2, DB::table('employee_annual_ticket_records')->where('employee_id', $this->emp->id)->count());
    }

    public function test_report_lists_unanswered_employees_until_hr_answers()
    {
        $this->actingAs($this->hr, 'resort-admin');
        $name = trim($this->emp->resortAdmin->first_name . ' ' . $this->emp->resortAdmin->last_name);
        $this->get(route('leave.annualTickets'))->assertOk()->assertSee('data-employee-id="' . $this->emp->id . '"', false);
        $this->postJson(route('leave.annualTickets.store'), ['employee_id' => $this->emp->id, 'company_provided' => 0])->assertOk();
        $this->get(route('leave.annualTickets'))->assertOk()->assertDontSee('data-employee-id="' . $this->emp->id . '"', false);
        $this->get(route('leave.annualTickets', ['show' => 'all']))->assertOk()->assertSee('data-employee-id="' . $this->emp->id . '"', false);
    }

    public function test_only_hr_can_answer_and_the_app_gets_the_same_answer()
    {
        $line = Employee::where('resort_id', $this->hr->resort_id)->where('status', 'Active')->where('rank', 6)->whereHas('resortAdmin')->get()
            ->first(fn ($e) => !Common::isHR($e)) ?? $this->markTestSkipped('No non-HR line worker.');
        Passport::actingAs($line->resortAdmin, [], 'api');
        $this->postJson('/api/resort/annual-leave-ticket', ['employee_id' => $this->emp->id, 'company_provided' => 1])->assertForbidden();

        $hrEmp = Employee::where('resort_id', $this->hr->resort_id)->where('status', 'Active')->whereHas('resortAdmin')->get()->first(fn ($e) => Common::isHR($e))
            ?? $this->markTestSkipped('No HR employee.');
        app('auth')->forgetGuards();
        Passport::actingAs($hrEmp->resortAdmin, [], 'api');
        $this->postJson('/api/resort/annual-leave-ticket', ['employee_id' => $this->emp->id, 'company_provided' => 1])->assertOk()->assertJsonPath('annual_ticket.company_provided', true);
        $this->assertTrue((bool) DB::table('employee_annual_ticket_records')->where('employee_id', $this->emp->id)->value('company_provided'));
    }
}
