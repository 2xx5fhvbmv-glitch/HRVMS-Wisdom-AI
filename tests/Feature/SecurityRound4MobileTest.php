<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\MonthlyCheckingModel;
use App\Models\ResortAdmin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** Security audit run-4, mobile API side. */
class SecurityRound4MobileTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();
        Mail::fake();
        // No real FCM push from tinker-style test runs.
        Employee::where('resort_id', $this->resortId)->update(['device_token' => null]);
    }

    private function app_as(string $login): ResortAdmin
    {
        $this->app['auth']->forgetGuards();
        foreach ($this->app['router']->getRoutes()->getRoutes() as $route) {
            $route->flushController();
        }
        $admin = ResortAdmin::where('email', "{$login}@demo.thewisdom.ai")->firstOrFail();
        Passport::actingAs($admin, [], 'api');
        return $admin;
    }

    private function foreignEmployee(): Employee
    {
        $e = Employee::where('resort_id', '!=', $this->resortId)->whereNotNull('Admin_Parent_id')->whereNotNull('Position_id')->first();
        if (!$e) {
            $this->markTestSkipped('needs an employee in another resort');
        }
        return $e;
    }

    // N4
    public function test_participant_lists_reject_zero_and_stay_in_the_resort(): void
    {
        $this->app_as('hr');
        foreach (['participant-feedback-from-list', 'participant-evaluation-from-list'] as $path) {
            $this->postJson("/api/learning/{$path}", ['training_schedule_id' => 0])->assertStatus(400);
        }

        $foreign = $this->foreignEmployee();
        $trainingId = 987654321;
        DB::table('training_feedback_responses')->insert(['form_id' => 1, 'training_id' => $trainingId,
            'participant_id' => $foreign->id, 'responses' => '{}', 'created_at' => now(), 'updated_at' => now()]);

        $this->app_as('hr');
        $this->postJson('/api/learning/participant-feedback-from-list', ['training_schedule_id' => $trainingId])
            ->assertOk()->assertJsonCount(0, 'feedback_listing');
    }

    // N11
    public function test_calendar_event_cannot_invite_another_resorts_employee(): void
    {
        $foreign = $this->foreignEmployee();
        $this->app_as('hod.kitchen');
        $this->postJson('/api/calendar/create-event', [
            'title' => 'x', 'date' => now()->format('Y-m-d'), 'time' => '10:00', 'reminder_days' => 1,
            'events_for' => 'employee', 'employees' => [$foreign->id],
        ])->assertStatus(400)->assertJsonValidationErrors(['employees.0'], 'errors');
    }

    // N12
    public function test_only_the_employees_managers_or_hr_reschedule_a_check_in(): void
    {
        $target = $this->emp('employee');
        $hr = $this->app_as('hr');
        $meeting = MonthlyCheckingModel::create(['Checkin_id' => 'RND4', 'resort_id' => $this->resortId, 'emp_id' => $target->id,
            'date_discussion' => now()->format('Y-m-d'), 'start_time' => '10:00', 'end_time' => '11:00', 'Meeting_Place' => 'HR',
            'Area_of_Discussion' => 'x', 'Area_of_Improvement' => 'x', 'Time_Line' => 'x', 'comment' => 'x', 'status' => 'Confirm', 'created_by' => $hr->id]);
        $payload = ['meeting_id' => $meeting->id, 'date_discussion' => now()->addDay()->format('Y-m-d'), 'venue' => 'Office', 'start_time' => '10:00', 'end_time' => '11:00'];

        $this->app_as('hod.kitchen');
        $this->postJson('/api/monthlycheckin/monthly-checkin-reschedule', $payload)->assertStatus(403);
        $this->assertSame('Confirm', $meeting->fresh()->status);

        $this->app_as('hr');
        $this->postJson('/api/monthlycheckin/monthly-checkin-reschedule', $payload)->assertOk()->assertJson(['status' => true]);
        $this->assertSame('Rescheduled', $meeting->fresh()->status);
    }

    // Employee list / leave detail
    public function test_ordinary_employee_sees_only_own_leave_data(): void
    {
        $me = $this->emp('employee');
        $other = $this->emp('hod.kitchen');

        $this->app_as('employee');
        $rows = collect($this->getJson("/api/resort/employees/{$this->resortId}")->assertOk()->json('employee_data'));
        $otherRow = $rows->firstWhere('employee.emp_id', $other->id);
        $this->assertNotNull($otherRow, 'colleague picker still lists the resort');
        $this->assertNull($otherRow['total_allocated_days']);
        $this->assertNull($otherRow['employee']['religion']);
        $this->assertNotNull($rows->firstWhere('employee.emp_id', $me->id)['total_allocated_days']);

        $this->app_as('employee');
        $this->getJson("/api/resort/employee-leave/{$other->id}")->assertStatus(403);
        $this->app_as('employee');
        $this->getJson("/api/resort/employee-leave/{$me->id}")->assertOk();
    }

    public function test_leave_detail_scope_for_hod_and_hr(): void
    {
        $housekeeper = $this->emp('employee');

        $this->app_as('hod.kitchen');
        $this->getJson("/api/resort/employee-leave/{$housekeeper->id}")->assertStatus(403);
        $this->app_as('hod.housekeeping');
        $this->getJson("/api/resort/employee-leave/{$housekeeper->id}")->assertOk();
        $this->app_as('hr');
        $this->getJson("/api/resort/employee-leave/{$housekeeper->id}")->assertOk();
    }

    public function test_supervisor_sees_leave_of_own_reports_only(): void
    {
        $sup = $this->emp('supervisor');
        $report = $this->emp('employee');
        DB::table('employees')->where('id', $report->id)->update(['reporting_to' => $sup->id]);
        $stranger = $this->emp('hod.kitchen');

        $this->app_as('supervisor');
        $this->getJson("/api/resort/employee-leave/{$report->id}")->assertOk();
        $this->app_as('supervisor');
        $this->getJson("/api/resort/employee-leave/{$stranger->id}")->assertStatus(403);
    }

    // Housekeeping
    public function test_housekeeping_services_by_grade_is_department_scoped(): void
    {
        $housekeeper = $this->emp('employee');
        $chef = $this->emp('hod.kitchen');

        $this->app_as('hod.kitchen');
        $this->getJson("/api/accommodation/housekeeping-requests/services-by-grade/{$housekeeper->id}")->assertStatus(403);
        $this->app_as('hod.kitchen');
        $this->postJson('/api/accommodation/housekeeping-requests/create', ['emp_id' => $housekeeper->id, 'service_ids' => [1]])->assertStatus(403);
        $this->app_as('hod.kitchen');
        $this->getJson("/api/accommodation/housekeeping-requests/services-by-grade/{$chef->id}")->assertOk();
        $this->app_as('hod.housekeeping');
        $this->getJson("/api/accommodation/housekeeping-requests/services-by-grade/{$housekeeper->id}")->assertOk();
    }

    // Shopkeeper id collision
    public function test_employee_cannot_see_or_mark_a_shopkeepers_notification_with_the_same_id(): void
    {
        $me = $this->emp('employee');
        $shopRow = DB::table('resort_notifications')->insertGetId(['resort_id' => $this->resortId, 'user_id' => $me->id,
            'module' => 'Staff Shop', 'type' => 'Payment Approved', 'message' => 'shop only', 'status' => 'unread',
            'created_at' => now(), 'updated_at' => now()]);

        $this->app_as('employee');
        $ids = collect($this->getJson('/api/notification/active-list')->json('notification_data') ?? [])->pluck('id');
        $this->assertNotContains($shopRow, $ids);

        $this->app_as('employee');
        $this->postJson('/api/notification/mark-read', ['notification_id' => $shopRow])->assertStatus(404);
        $this->assertSame('unread', DB::table('resort_notifications')->where('id', $shopRow)->value('status'));
    }

    // Route middleware
    public function test_device_token_and_clinic_doctor_login_middleware(): void
    {
        $mw = fn (string $uri) => Route::getRoutes()->match(\Illuminate\Http\Request::create("/api/{$uri}", 'POST'))->gatherMiddleware();
        $this->assertContains('account.active', $mw('add-device-token'));
        $this->assertContains('throttle:mobile-login', $mw('clinic-doctor/login'));
    }
}
