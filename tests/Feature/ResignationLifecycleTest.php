<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** Founder 2026-10-15: pending withdrawal holds Inactive; HOD acceptance notice; mobile sees the withdrawal stage. */
class ResignationLifecycleTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    private int $staffId;
    private int $resignationId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();
        // Push notifications are raw curl (Http::fake can't stop them) — no device may receive one from a test.
        DB::table('employees')->update(['device_token' => null]);
        $this->staffId = $this->emp('employee')->id;
        $this->resignationId = DB::table('employee_resignation')->insertGetId(['resort_id' => $this->resortId, 'employee_id' => $this->staffId,
            'reason' => 'Relocation', 'resignation_date' => now()->subMonth()->toDateString(), 'last_working_day' => now()->subDay()->toDateString(),
            'hod_id' => $this->emp('hod.housekeeping')->id, 'hr_id' => $this->emp('hr')->id,
            'status' => 'Pending', 'hod_status' => 'Pending', 'hr_status' => 'Pending', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_hod_acceptance_tells_the_employee_it_is_now_with_hr()
    {
        $this->as('hod.housekeeping')->withoutMiddleware(VerifyCsrfToken::class)
            ->postJson(route('people.employee-resignation.status-update'), ['resignation_id' => base64_encode($this->resignationId), 'status' => 'Approved'])
            ->assertJson(['success' => true]);
        $note = DB::table('resort_notifications')->where('user_id', $this->staffId)->where('type', 'Resignation Accepted by HOD')->first();
        $this->assertNotNull($note);
        $this->assertStringContainsString('now with HR for final approval', $note->message);
        $this->assertSame($this->resignationId, (int) $note->request_id);
    }

    public function test_a_pending_withdrawal_holds_back_inactive_until_decided()
    {
        DB::table('employees')->where('id', $this->staffId)->update(['status' => 'Offboarding']);
        DB::table('employee_resignation')->where('id', $this->resignationId)->update(['status' => 'Approved', 'hod_status' => 'Approved',
            'hr_status' => 'Approved', 'withdraw_hod_status' => 'Pending', 'withdraw_hr_status' => 'Pending']);

        $this->artisan('employees:apply-last-working-day')->assertSuccessful();
        $this->assertSame('Offboarding', DB::table('employees')->where('id', $this->staffId)->value('status'), 'held while the HOD decides');

        DB::table('employee_resignation')->where('id', $this->resignationId)->update(['withdraw_hod_status' => 'Approved']);
        $this->artisan('employees:apply-last-working-day')->assertSuccessful();
        $this->assertSame('Offboarding', DB::table('employees')->where('id', $this->staffId)->value('status'), 'held while HR decides');

        DB::table('employee_resignation')->where('id', $this->resignationId)->update(['withdraw_hr_status' => 'Rejected']);
        $this->artisan('employees:apply-last-working-day')->assertSuccessful();
        $this->assertSame('Inactive', DB::table('employees')->where('id', $this->staffId)->value('status'), 'rejected → the LWD job proceeds');
    }

    public function test_mobile_dashboard_returns_the_withdrawal_stage()
    {
        DB::table('employee_resignation')->where('id', $this->resignationId)->update(['status' => 'Approved', 'hod_status' => 'Approved',
            'hr_status' => 'Approved', 'withdraw_hod_status' => 'Approved', 'withdraw_hr_status' => 'Pending', 'withdraw_reason' => 'Changed my mind']);
        $this->app['auth']->forgetGuards();
        $admin = \App\Models\ResortAdmin::find($this->emp('employee')->Admin_Parent_id);
        $this->actingAs($admin, 'api')->getJson('/api/resignation/resignation-dashboard')
            ->assertOk()
            ->assertJsonPath('data.withdrawal.stage', 'pending_hr')
            ->assertJsonPath('data.withdrawal.hod_status', 'Approved')
            ->assertJsonPath('data.withdrawal.reason', 'Changed my mind');
    }
}
