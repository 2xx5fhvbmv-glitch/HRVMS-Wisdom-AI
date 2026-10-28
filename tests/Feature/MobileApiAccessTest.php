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

    public function test_reissued_credentials_must_be_changed_at_first_login()
    {
        $employee = Employee::where('status', 'Active')->where('employment_type', 'Full-Time')
            ->whereHas('resortAdmin', fn ($q) => $q->whereNotNull('email'))->firstOrFail();
        $employee->resortAdmin->forceFill(['must_change_password' => 0])->save();

        Common::issueLoginCredentials($employee, $employee->resort);
        $this->assertEquals(1, $employee->resortAdmin->fresh()->must_change_password);
    }
}
