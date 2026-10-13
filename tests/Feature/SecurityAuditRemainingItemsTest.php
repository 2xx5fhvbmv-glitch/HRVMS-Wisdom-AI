<?php

namespace Tests\Feature;

use App\Helpers\Common;
use App\Models\Employee;
use App\Models\ResortAdmin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Security audit 2026-10-07 — acceptance for the three items the hand-off
 * listed as open: L-01 (viewLeaveRequest gate), SO-02 (live-emergency SOS
 * actions) and AC-02 (staff maintenance ownership). Runs against existing
 * local data inside a rolled-back transaction.
 */
class SecurityAuditRemainingItemsTest extends TestCase
{
    use DatabaseTransactions;

    private const RESORT = 26;

    private function admin(Employee $e): ResortAdmin
    {
        return ResortAdmin::findOrFail($e->Admin_Parent_id);
    }

    private function web(Employee $e)
    {
        $this->app['auth']->forgetGuards();
        return $this->withServerVariables(['HTTPS' => 'on'])->actingAs($this->admin($e), 'resort-admin');
    }

    private function active(array $where)
    {
        return Employee::where('resort_id', self::RESORT)->where('status', 'Active')->where($where);
    }

    // ------------------------------------------------------------------ L-01
    // viewLeaveRequest() hands the record to Common::canViewLeave() in the shape
    // below (employees_leaves row). The endpoint's SQL uses JSON_ARRAYAGG, which
    // the local MariaDB 10.4 lacks, so the matrix is asserted on the gate itself.

    private function leaveWithChain()
    {
        return DB::table('employees_leaves as l')->join('employees_leaves_status as s', 's.leave_request_id', '=', 'l.id')
            ->where('l.resort_id', self::RESORT)->orderBy('l.id')->select('l.*')->first();
    }

    public function test_view_leave_request_gate_matrix()
    {
        $leave = $this->leaveWithChain();
        if (!$leave) $this->markTestSkipped('No leave with approval chain.');
        $owner = Employee::find($leave->emp_id);
        $gate = fn(Employee $e) => Common::canViewLeave(clone $leave, $e);

        $this->assertTrue($gate($owner), 'applicant');

        $approverId = DB::table('employees_leaves_status')->where('leave_request_id', $leave->id)->where('approver_id', '!=', $owner->id)->value('approver_id');
        if ($approverId) $this->assertTrue($gate(Employee::find($approverId)), 'approver in chain');

        $hr = Employee::whereIn('id', Common::getResortHrEmployeeIds(self::RESORT))->where('status', 'Active')->first();
        if ($hr) $this->assertTrue($gate($hr), 'HR');

        // unrelated colleague: same resort, ordinary rank, not in the chain
        $colleague = $this->active([])->whereIn('rank', [5, 6])->where('id', '!=', $owner->id)
            ->whereNotExists(fn($q) => $q->select(DB::raw(1))->from('employees_leaves_status')->where('leave_request_id', $leave->id)->whereColumn('approver_id', 'employees.id'))
            ->first();
        if ($colleague) $this->assertFalse($gate($colleague), 'unrelated colleague (sick note must not be reachable)');

        $foreign = Employee::where('resort_id', '!=', self::RESORT)->where('status', 'Active')->first();
        if ($foreign) $this->assertFalse($gate($foreign), 'other resort');
    }

    public function test_view_leave_request_endpoint_is_gated_by_can_view_leave()
    {
        $src = file_get_contents(app_path('Http/Controllers/API/LeaveController.php'));
        $body = substr($src, strpos($src, 'public function viewLeaveRequest('), 16000);
        $gate = strpos($body, 'Common::canViewLeave(');
        $attach = strpos($body, 'resolveLeaveAttachmentUrl(');
        $this->assertNotFalse($gate, 'viewLeaveRequest must call Common::canViewLeave');
        $this->assertLessThan($attach, $gate, 'gate must run before the attachment URL is resolved');
    }

    // ----------------------------------------------------------------- SO-02

    private function sosCalls(): array
    {
        return [
            'updateStatus'          => fn($c) => $c->post(route('sos.emergency.update.status'), []),
            'destroy'               => fn($c) => $c->delete(route('sos.emergency.destroy', 0)),
            'updateMassInstruction' => fn($c) => $c->post(route('sos.updateMassInstruction'), []),
        ];
    }

    public function test_sos_live_actions_denied_for_hr_and_ordinary_users()
    {
        $hr = Employee::whereIn('id', Common::getResortHrEmployeeIds(self::RESORT))->where('status', 'Active')->first();
        $ordinary = $this->active([])->whereIn('rank', [5, 6])->first();
        $viewers = array_filter(['HR' => $hr, 'ordinary' => $ordinary]);
        if (!$viewers) $this->markTestSkipped('no viewers');
        foreach ($viewers as $label => $emp) {
            foreach ($this->sosCalls() as $name => $call) {
                $this->assertSame(403, $call($this->web($emp))->status(), "$label → $name");
            }
        }
    }

    public function test_sos_live_actions_allowed_for_security_manager()
    {
        $sm = $this->active([])->whereHas('position', fn($q) => $q->where('position_title', 'Security Manager'))->first();
        if (!$sm) $this->markTestSkipped('no Security Manager in resort');
        foreach ($this->sosCalls() as $name => $call) {
            // past the role gate: validation (422) / not-found (404) / ok — never 403
            $this->assertNotSame(403, $call($this->web($sm)->withHeader('Accept', 'application/json'))->status(), $name);
        }
    }

    // ----------------------------------------------------------------- AC-02

    private function maintenanceRequest()
    {
        return DB::table('maintanace_requests')->where('resort_id', self::RESORT)->whereNotNull('Raised_By')->orderByDesc('id')->first();
    }

    private function colleagueOf($req): ?Employee
    {
        return $this->active([])->where('id', '!=', $req->Raised_By)->first();
    }

    public function test_colleague_cannot_edit_or_complete_a_maintenance_request()
    {
        $req = $this->maintenanceRequest();
        if (!$req) $this->markTestSkipped('no maintenance request');
        $colleague = $this->colleagueOf($req);
        Passport::actingAs($this->admin($colleague), [], 'api');

        // make it "waiting for the raiser's confirmation" so only ownership can stop the close
        DB::table('maintanace_requests')->where('id', $req->id)->update(['Status' => 'Resolvedawaiting']);

        $this->postJson('/api/accommodation/staff-accommodation-complete-task-status', ['request_id' => $req->id]);
        $this->assertSame('resolvedawaiting', strtolower(DB::table('maintanace_requests')->where('id', $req->id)->value('Status')), 'colleague closed it');

        $r = $this->postJson('/api/accommodation/edit-maintenance-requests', [
            'request_id' => $req->id, 'item_id' => $req->item_id, 'building_id' => $req->building_id, 'FloorNo' => '9',
            'RoomNo' => '999', 'descriptionIssues' => 'tampered by colleague', 'priority' => 'High',
        ]);
        $this->assertStringContainsString('not found', strtolower($r->getContent()), 'colleague edit must be not-found');
        $this->assertNotSame('tampered by colleague', DB::table('maintanace_requests')->where('id', $req->id)->value('descriptionIssues'));
    }

    public function test_raiser_can_edit_own_maintenance_request()
    {
        $req = $this->maintenanceRequest();
        if (!$req) $this->markTestSkipped('no maintenance request');
        $raiser = Employee::find($req->Raised_By);
        Passport::actingAs($this->admin($raiser), [], 'api');

        $r = $this->postJson('/api/accommodation/edit-maintenance-requests', [
            'request_id' => $req->id, 'item_id' => $req->item_id, 'building_id' => $req->building_id, 'FloorNo' => $req->FloorNo ?: '1',
            'RoomNo' => $req->RoomNo ?: '1', 'descriptionIssues' => 'edited by raiser', 'priority' => 'Low',
        ]);
        $this->assertTrue((bool) $r->json('status'), $r->getContent());
        $this->assertSame('edited by raiser', DB::table('maintanace_requests')->where('id', $req->id)->value('descriptionIssues'));
    }
}
