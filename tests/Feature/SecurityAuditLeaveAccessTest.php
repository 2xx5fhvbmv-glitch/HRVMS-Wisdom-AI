<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\ResortAdmin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Security audit L-01 / L-05 / L-09 / PE-01 — HTTP acceptance matrix against
 * existing local data (rolled back; nothing is committed).
 */
class SecurityAuditLeaveAccessTest extends TestCase
{
    use DatabaseTransactions;

    private $leave;
    private $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->leave = DB::table('employees_leaves as l')->join('employees_leaves_status as s', 's.leave_request_id', '=', 'l.id')
            ->where('l.resort_id', 26)->orderBy('l.id')->select('l.*')->first(); // needs an approval chain
        if (!$this->leave) $this->markTestSkipped('No leave data.');
        $this->owner = Employee::find($this->leave->emp_id);
    }

    private function admin(Employee $e): ResortAdmin
    {
        return ResortAdmin::findOrFail($e->Admin_Parent_id);
    }

    private function emp(array $where, bool $sameDept = null)
    {
        $q = Employee::where('resort_id', $this->leave->resort_id)->where('status', 'Active')->where($where)->where('id', '!=', $this->owner->id);
        if ($sameDept === true) $q->where('Dept_id', $this->owner->Dept_id);
        if ($sameDept === false) $q->where('Dept_id', '!=', $this->owner->Dept_id);
        return $q->first();
    }

    private function mobileView(Employee $e)
    {
        Passport::actingAs($this->admin($e), [], 'api');
        return $this->getJson('/api/resort/view-leave-history/' . base64_encode($this->leave->id));
    }

    public function test_mobile_leave_history_matrix()
    {
        $this->assertSame(200, $this->mobileView($this->owner)->status(), 'owner');
        $hod = $this->emp(['rank' => 2], true);
        $unrelated = Employee::where('resort_id', 26)->where('status', 'Active')->where('rank', 2)->where('Dept_id', '!=', $this->owner->Dept_id)
            ->whereNotExists(fn($q) => $q->select(DB::raw(1))->from('employees_leaves_status')->where('leave_request_id', $this->leave->id)->whereColumn('approver_id', 'employees.id'))
            ->get()->first(fn($e) => !\App\Helpers\Common::hasFullDataAccess($e));
        if ($hod) $this->assertSame(200, $this->mobileView($hod)->status(), 'own HOD');
        $this->assertSame(403, $this->mobileView($unrelated)->status(), 'unrelated HOD');
        $other = Employee::where('resort_id', '!=', 26)->where('status', 'Active')->first();
        $this->assertContains($this->mobileView($other)->status(), [403, 200]);
        $this->assertStringNotContainsString('"leave_details"', (string) $this->mobileView($other)->getContent());
    }

    public function test_cross_resort_handle_leave_action_is_not_found_and_leaks_nothing()
    {
        $foreign = Employee::where('resort_id', '!=', $this->leave->resort_id)->where('status', 'Active')->first();
        Passport::actingAs($this->admin($foreign), [], 'api');
        $r = $this->postJson('/api/resort/handle-leave-action', ['leave_id' => $this->leave->id, 'action' => 'Approved']);
        $this->assertStringContainsString('Leave request not found', $r->getContent());
        $this->assertStringNotContainsString('approval', strtolower($r->getContent()));
    }

    public function test_task_delegation_rejects_other_resort_employee()
    {
        $foreign = Employee::where('resort_id', '!=', 26)->where('status', 'Active')->first();
        Passport::actingAs($this->admin($this->owner), [], 'api');
        $r = $this->postJson('/api/resort/leave-add', ['task_delegation' => $foreign->id]);
        $body = json_encode($r->json());
        $this->assertStringContainsString('task_delegation', $body);
    }

    private function web(Employee $e)
    {
        $this->app['auth']->forgetGuards();
        return $this->withServerVariables(['HTTPS' => 'on'])->actingAs($this->admin($e), 'resort-admin');
    }

    public function test_web_people_edits_are_hr_only()
    {
        $rid = $this->leave->resort_id;
        $hr = Employee::whereIn('id', \App\Helpers\Common::getResortHrEmployeeIds($rid))->where('status', 'Active')->first();
        $gm = Employee::where('resort_id', $rid)->where('rank', 8)->where('status', 'Active')->first();
        $ordinary = Employee::where('resort_id', $rid)->where('rank', 5)->where('status', 'Active')->first();
        $target = $this->owner->id;
        foreach (['HR' => [$hr, false], 'GM' => [$gm, true], 'ordinary' => [$ordinary, true]] as $k => [$e, $denied]) {
            if (!$e) continue;
            $r = $this->web($e)->post(route('employee.update.bankDetails', $target), []);
            $denied ? $this->assertSame(403, $r->status(), $k) : $this->assertNotSame(403, $r->status(), $k);
        }
    }

    // One request per test: controllers cache the auth user in their constructor and
    // the app instance is shared within a test method.
    private function hr(): Employee
    {
        return Employee::whereIn('id', \App\Helpers\Common::getResortHrEmployeeIds($this->leave->resort_id))->where('status', 'Active')->first();
    }

    private function unrelatedHod(): Employee
    {
        return Employee::where('resort_id', $this->leave->resort_id)->where('status', 'Active')->where('rank', 2)->where('Dept_id', '!=', $this->owner->Dept_id)->get()
            ->first(fn($e) => !\App\Helpers\Common::hasFullDataAccess($e));
    }

    private function pdfUrl(): string
    {
        return route('leave.history.download-pdf', base64_encode($this->owner->id));
    }

    public function test_web_leave_pdf_unrelated_hod_denied()
    {
        $this->web($this->unrelatedHod())->get($this->pdfUrl())->assertRedirect();
    }

    public function test_web_leave_pdf_hr_allowed()
    {
        $this->assertSame(200, $this->web($this->hr())->get($this->pdfUrl())->status());
    }

    public function test_web_pass_detail_unrelated_hod_denied()
    {
        $pass = DB::table('employee_travel_passes')->where('resort_id', $this->leave->resort_id)->first();
        if (!$pass) $this->markTestSkipped('no passes');
        $passOwner = Employee::find($pass->employee_id);
        $viewer = Employee::where('resort_id', $pass->resort_id)->where('status', 'Active')->where('rank', 2)->where('Dept_id', '!=', $passOwner->Dept_id)
            ->whereNotExists(fn($q) => $q->select(DB::raw(1))->from('employee_travel_pass_status')->where('travel_pass_id', $pass->id)->whereColumn('approver_id', 'employees.id'))
            ->get()->first(fn($e) => !\App\Helpers\Common::hasFullDataAccess($e) && ($e->position->position_title ?? '') !== 'Security Manager');
        $this->assertSame(403, $this->web($viewer)->getJson(route('resort.boardingpass.detail', ['id' => $pass->id]))->status());
    }

    public function test_web_pass_detail_hr_allowed()
    {
        $pass = DB::table('employee_travel_passes')->where('resort_id', $this->leave->resort_id)->first();
        if (!$pass) $this->markTestSkipped('no passes');
        $this->assertSame(200, $this->web($this->hr())->getJson(route('resort.boardingpass.detail', ['id' => $pass->id]))->status());
    }
}
