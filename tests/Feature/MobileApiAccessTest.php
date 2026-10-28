<?php

namespace Tests\Feature;

use App\Helpers\Common;
use App\Models\Employee;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Laravel\Passport\Passport;
use Tests\TestCase;

/** Security audit wave 1, mobile API and credentials: W1-11/29/34. */
class MobileApiAccessTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
    }

    /** Active line worker outside HR and Security: no full data access, can't manage manifests. */
    private function lineWorker(): Employee
    {
        return Employee::where('status', 'Active')->where('rank', 6)->whereHas('resortAdmin', fn ($q) => $q->where('status', 'Active'))->get()
            ->first(fn ($e) => !Common::isHRDepartment($e->Dept_id) && !Common::isSecurityDepartment($e->Dept_id))
            ?? $this->markTestSkipped('No active line worker outside HR/Security.');
    }

    public function test_ordinary_employee_cannot_push_to_the_resort_or_close_a_manifest()
    {
        Passport::actingAs($this->lineWorker()->resortAdmin, [], 'api');
        $this->getJson('/api/resort/test-push-notification')->assertForbidden();
        $this->postJson('/api/boarding/boarding-pass-status-update', ['manifest_type' => 'departure'])->assertForbidden();
    }

    public function test_hod_of_another_department_is_refused_hr_only_actions()
    {
        $hods = Employee::where('status', 'Active')->where('rank', 2)->whereHas('resortAdmin', fn ($q) => $q->where('status', 'Active'))->get();
        $housekeeping = fn ($e) => in_array($e->id, Common::getResortHousekeepingHodXcomEmployeeIds($e->resort_id));
        $hod = $hods->first(fn ($e) => !Common::isHR($e) && !$housekeeping($e)) ?? $this->markTestSkipped('No HOD outside HR/Housekeeping.');
        Passport::actingAs($hod->resortAdmin, [], 'api');   // passes check.rank:HR,GM,HOD,EXCOM; the method gate must refuse
        $this->postJson('/api/accommodation/hr-maintenance-req-action', ['request_id' => 1, 'action' => 'Approved'])->assertForbidden();
        $this->postJson('/api/employee-management/hr-employee-overview', [])->assertForbidden();
        $this->postJson('/api/accommodation/housekeeping-add-schedules', [])->assertForbidden();
    }

    // Own test: the router reuses a controller (and its constructor's user) within one test, unlike real requests.
    public function test_housekeeping_hod_can_still_raise_a_schedule()
    {
        $hk = Employee::where('status', 'Active')->where('rank', 2)->whereHas('resortAdmin', fn ($q) => $q->where('status', 'Active'))->get()
            ->first(fn ($e) => in_array($e->id, Common::getResortHousekeepingHodXcomEmployeeIds($e->resort_id))) ?? $this->markTestSkipped('No Housekeeping HOD.');
        Passport::actingAs($hk->resortAdmin, [], 'api');
        $this->assertNotSame(403, $this->postJson('/api/accommodation/housekeeping-add-schedules', [])->status());
    }

    public function test_mobile_user_with_a_temporary_password_can_only_change_it()   // W1-30
    {
        $user = $this->lineWorker()->resortAdmin;
        $user->forceFill(['must_change_password' => 1])->save();
        Passport::actingAs($user, [], 'api');
        $this->assertNotSame(403, $this->getJson('/api/resort/profile')->status());
        $this->getJson('/api/resort/test-push-notification')->assertForbidden()->assertJson(['must_change_password' => true]);
    }

    public function test_reissued_credentials_must_be_changed_at_first_login()
    {
        $employee = Employee::where('status', 'Active')->where('employment_type', 'Full-Time')
            ->whereHas('resortAdmin', fn ($q) => $q->whereNotNull('email'))->firstOrFail();
        $employee->resortAdmin->forceFill(['must_change_password' => 0])->save();

        Common::issueLoginCredentials($employee, $employee->resort);
        $this->assertEquals(1, $employee->resortAdmin->fresh()->must_change_password);
    }
}
