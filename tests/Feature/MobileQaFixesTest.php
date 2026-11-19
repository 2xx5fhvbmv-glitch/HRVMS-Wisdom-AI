<?php

namespace Tests\Feature;

use App\Helpers\Common;
use App\Helpers\StorageHelper;
use App\Models\Employee;
use App\Models\JobDescriptionEmployeeRecord;
use App\Models\ResortAdmin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Passport\Passport;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** Backend fixes for the mobile-app QA report (2026-11-18): items 1, 3, 5, 6, 8, 9, 11, 12, 13, 15, 17, 18. */
class MobileQaFixesTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();
        Mail::fake();
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

    private function png(callable $draw, int $w = 600, int $h = 200): string
    {
        $im = imagecreatetruecolor($w, $h);
        $draw($im);
        ob_start();
        imagepng($im);
        return ob_get_clean();
    }

    private function penSignature(): string
    {
        return $this->png(function ($im) {
            imagefill($im, 0, 0, imagecolorallocate($im, 250, 250, 250));
            $ink = imagecolorallocate($im, 20, 20, 90);
            imagesetthickness($im, 4);
            for ($i = 0; $i < 8; $i++) {
                imageline($im, 60 + $i * 60, 150 - ($i % 2) * 90, 110 + $i * 60, 60 + ($i % 2) * 90, $ink);
            }
        });
    }

    /** Give a login a profile signature (encrypted at rest, like the real flow). */
    private function giveSignature(Employee $emp, string $bytes): void
    {
        $path = "demo/sig/{$emp->id}/signature.png";
        StorageHelper::put($path, Common::encryptFileBytes($bytes));
        DB::table('resort_admins')->where('id', $emp->Admin_Parent_id)->update(['signature_img' => $path]);
    }

    private function jdRecord(Employee $employee, Employee $director, array $extra = []): JobDescriptionEmployeeRecord
    {
        $jdId = DB::table('job_descriptions')->insertGetId(['Resort_id' => $this->resortId, 'Department_id' => $employee->Dept_id,
            'Position_id' => $employee->Position_id, 'jobdescription' => '<p>Serve guests.</p>', 'created_at' => now(), 'updated_at' => now()]);
        return JobDescriptionEmployeeRecord::create($extra + [
            'resort_id' => $this->resortId, 'job_description_id' => $jdId, 'employee_id' => $employee->id,
            'employer_signatory_employee_id' => $director->id, 'employer_signatory_id_card_number' => 'A1234567',
            'employee_dob' => '1990-01-01', 'employee_id_card_number' => 'P7654321', 'employee_current_address' => 'Malé',
            'status' => 'Pending', 'sent_at' => now(),
        ]);
    }

    public function test_1_3_signing_never_overwrites_the_employer_signature_and_the_detail_hides_personal_data()
    {
        $director = $this->emp('hr');
        $employee = $this->emp('employee');
        $this->giveSignature($director, $this->penSignature());
        $this->giveSignature($employee, $this->png(fn ($im) => imagefill($im, 0, 0, imagecolorallocate($im, 10, 10, 10)), 40, 40));

        // a record issued before the fix: employer signature in the old shared file
        $record = $this->jdRecord($employee, $director);
        $old = Common::snapshotSignature($director->Admin_Parent_id, 'job_description', $record->id);
        $record->update(['employer_signature_path' => $old['signature_img']]);
        $employerBytes = StorageHelper::get($old['signature_img']);

        $this->app_as('employee');
        $this->getJson("api/job-description/{$record->id}")->assertOk()
            ->assertJsonMissingPath('data.employee_dob')->assertJsonMissingPath('data.employee_id_card_number')
            ->assertJsonMissingPath('data.employee_current_address')->assertJsonMissingPath('data.employer_signatory_id_card_number')
            ->assertJsonMissingPath('data.employer_signature_path')->assertJsonMissingPath('data.employee_signature_path')
            ->assertJsonPath('data.can_act', true)->assertJsonPath('data.job_description.jobdescription', '<p>Serve guests.</p>');

        $this->postJson("api/job-description/{$record->id}/consent")->assertOk()->assertJson(['success' => true]);
        $record->refresh();
        $this->assertStringContainsString('/approvals/job_description_employee/', $record->employee_signature_path);
        $this->assertNotSame($record->employer_signature_path, $record->employee_signature_path);
        $this->assertSame($employerBytes, StorageHelper::get($record->employer_signature_path), 'employer signature untouched');
    }

    public function test_1_the_repair_command_restores_employer_signatures_on_old_records()
    {
        $director = $this->emp('hr');
        $employee = $this->emp('employee');
        $this->giveSignature($director, $this->penSignature());

        $this->giveSignature($employee, $this->png(fn ($im) => imagefill($im, 0, 0, imagecolorallocate($im, 10, 10, 10)), 40, 40));
        $signed = $this->jdRecord($employee, $director, ['status' => 'Signed', 'signed_at' => now()]);
        // what happened on old records: the employee's sign-off overwrote the shared file both paths point at
        $shared = Common::snapshotSignature($employee->Admin_Parent_id, 'job_description', $signed->id);
        $signed->update(['employer_signature_path' => $shared['signature_img'], 'employee_signature_path' => $shared['signature_img']]);

        $pending = $this->jdRecord($this->emp('supervisor'), $director);
        $old = Common::snapshotSignature($director->Admin_Parent_id, 'job_description', $pending->id);
        $pending->update(['employer_signature_path' => $old['signature_img']]);

        $this->artisan('job-description:repair-signatures')->assertSuccessful();

        $signed->refresh();
        $pending->refresh();
        $this->assertStringContainsString('/approvals/job_description_employer/', $signed->employer_signature_path);
        $this->assertNotSame($signed->employer_signature_path, $signed->employee_signature_path);
        $this->assertStringContainsString('/approvals/job_description_employer/', $pending->employer_signature_path);
        $this->assertSame(StorageHelper::get($old['signature_img']), StorageHelper::get($pending->employer_signature_path), 'pending: original copied');
    }

    public function test_1_repair_falls_back_to_the_resorts_hr_director_when_the_record_has_no_signatory()
    {
        $director = Common::resolveHrDirector($this->resortId)['employee'];
        $this->assertNotNull($director);
        $this->giveSignature($director, $this->penSignature());
        $employee = $this->emp('employee');
        $this->giveSignature($employee, $this->png(fn ($im) => imagefill($im, 0, 0, imagecolorallocate($im, 10, 10, 10)), 40, 40));

        // prod JD #4/#5: signed, shared file, and no signatory id on the record
        $signed = $this->jdRecord($employee, $director, ['status' => 'Signed', 'signed_at' => now()]);
        $shared = Common::snapshotSignature($employee->Admin_Parent_id, 'job_description', $signed->id);
        $signed->update(['employer_signature_path' => $shared['signature_img'], 'employee_signature_path' => $shared['signature_img'],
            'employer_signatory_employee_id' => null, 'employer_signatory_name' => 'Someone Else']);

        $this->artisan('job-description:repair-signatures')->assertSuccessful();

        $signed->refresh();
        $this->assertStringContainsString('/approvals/job_description_employer/', $signed->employer_signature_path);
        $this->assertSame($director->id, (int) $signed->employer_signatory_employee_id);
        $this->assertNotSame('Someone Else', $signed->employer_signatory_name);
    }

    public function test_5_a_photo_is_not_accepted_as_a_signature()
    {
        $grass = $this->png(function ($im) {
            for ($y = 0; $y < 200; $y++) {
                for ($x = 0; $x < 600; $x++) {
                    imagesetpixel($im, $x, $y, imagecolorallocate($im, 40 + ($x * $y) % 60, 120 + ($x + $y) % 80, 30));
                }
            }
        });
        $this->assertNotNull(Common::signatureImageProblem(Common::removeSignatureBackground($grass)));
        $this->assertNotNull(Common::signatureImageProblem(Common::removeSignatureBackground($this->png(fn ($im) => imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255))))));
        $this->assertNull(Common::signatureImageProblem(Common::removeSignatureBackground($this->penSignature())));

        $this->app_as('employee');
        $file = UploadedFile::fake()->createWithContent('grass.png', $grass);
        $this->postJson('api/resort/profile-signature-preview', ['signature_image' => $file])->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_6_every_pending_leave_list_shows_only_leaves_waiting_for_this_user()
    {
        $requester = $this->emp('employee');
        $first = $this->emp('manager');
        $hr = $this->emp('hr');
        $leaveId = DB::table('employees_leaves')->insertGetId(['resort_id' => $this->resortId, 'emp_id' => $requester->id, 'leave_category_id' =>
            DB::table('leave_categories')->where('resort_id', $this->resortId)->value('id'), 'from_date' => now()->addDays(3)->toDateString(),
            'to_date' => now()->addDays(4)->toDateString(), 'total_days' => 2, 'status' => 'Pending', 'created_at' => now(), 'updated_at' => now()]);
        $s1 = DB::table('employees_leaves_status')->insertGetId(['leave_request_id' => $leaveId, 'approver_id' => $first->id, 'approver_rank' => $first->rank, 'status' => 'Pending', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('employees_leaves_status')->insert(['leave_request_id' => $leaveId, 'approver_id' => $hr->id, 'approver_rank' => $hr->rank, 'status' => 'Pending', 'created_at' => now(), 'updated_at' => now()]);

        $this->assertArrayHasKey($leaveId, Common::leavesAwaitingApprover($this->resortId, $first->id));
        $this->assertArrayNotHasKey($leaveId, Common::leavesAwaitingApprover($this->resortId, $hr->id), 'not HR\'s turn yet');

        $ids = fn ($res) => array_column($res->json('data.leave_requests') ?? $res->json('data.timeAttendance.leave_requests') ?? collect($res->json())->dot()
            ->filter(fn ($v, $k) => str_contains($k, 'leave_requests.') && str_ends_with($k, '.id'))->values()->map(fn ($id) => ['id' => $id])->all(), 'id');
        $this->app_as('hr');
        $this->assertNotContains($leaveId, $ids($this->getJson('api/timeandattendance/time-attendance-hr-dashboard')->assertOk()));

        DB::table('employees_leaves_status')->where('id', $s1)->update(['status' => 'Approved']);   // first step done → HR's turn
        $this->app_as('hr');
        $res = $this->getJson('api/timeandattendance/time-attendance-hr-dashboard')->assertOk();
        $this->assertContains($leaveId, $ids($res));
        $this->assertContains($leaveId, collect($this->getJson('api/resort/leave-dashboard-hr')->assertOk()->json('leave_detail.leave_request') ?? [])->pluck('id')->all());
    }

    public function test_8_upcoming_leave_never_lists_leave_that_has_ended()
    {
        $emp = $this->emp('employee');
        $cat = DB::table('leave_categories')->where('resort_id', $this->resortId)->value('id');
        $mk = fn ($from, $to) => DB::table('employees_leaves')->insertGetId(['resort_id' => $this->resortId, 'emp_id' => $emp->id, 'leave_category_id' => $cat,
            'from_date' => $from, 'to_date' => $to, 'total_days' => 1, 'status' => 'Approved', 'created_at' => now(), 'updated_at' => now()]);
        $mk(now()->startOfYear()->toDateString(), now()->startOfYear()->toDateString());   // ended
        $ongoing = now()->toDateString();
        $mk($ongoing, $ongoing);

        $this->app_as('hr');
        $body = json_encode($this->postJson('api/resort/upcoming-employee-leave-list', ['filter' => 'yearly'])->assertOk()->json());
        $this->assertStringNotContainsString(now()->startOfYear()->toDateString(), $body);
        $this->assertStringContainsString($ongoing, $body);
    }

    public function test_12_a_failed_upload_leaves_no_document_row()
    {
        $this->app_as('employee');
        $before = DB::table('employees_documents')->count();
        // storage refuses every write (e.g. the bucket is blocked): the upload fails and no document row may be left
        $disk = \Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class)->shouldIgnoreMissing(false);
        (new \ReflectionClass(StorageHelper::class))->setStaticPropertyValue('cachedDisk', $disk);

        $this->post('api/resort/employees-docs', ['document_title' => 'Passport', 'document_category' => 'Personal',
            'document_path' => UploadedFile::fake()->create('passport.pdf', 10, 'application/pdf')])->assertJson(['success' => false]);
        $this->assertSame($before, DB::table('employees_documents')->count());
    }

    public function test_13_names_cannot_be_phone_numbers_and_phones_need_a_country_code()
    {
        $this->assertSame('+9607654321', Common::normalizePhone('+960 765 4321'));
        $this->assertNull(Common::normalizePhone('9607731234'));
        $this->assertNull(Common::normalizePhone('+960 91949870987'));
        $this->assertNull(Common::normalizePhone('+960 960'));

        $this->app_as('employee');
        $post = fn ($d) => $this->postJson('api/resort/profile-personal-update', $d + ['first_name' => 'Ana', 'last_name' => 'Silva', 'personal_phone' => '+960 765 4321']);
        $post(['last_name' => '+960 7654321'])->assertStatus(400);
        $post(['personal_phone' => '9607731234'])->assertStatus(400);
        $post([])->assertOk();
        $payload = DB::table('employee_info_update_request')->orderByDesc('id')->value('info_payload');
        $this->assertSame('+9607654321', json_decode($payload, true)['personal_phone']);
    }

    public function test_17_wrong_current_passwords_lock_the_account_and_answers_carry_success()
    {
        $admin = $this->app_as('employee');
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('api/resort/profile-change-password', ['old_password' => 'wrong-' . $i, 'password' => 'NewPassw0rd!xyz', 'confirm_password' => 'NewPassw0rd!xyz'])
                ->assertOk()->assertJson(['success' => false, 'status' => false]);
        }
        $this->assertTrue(Common::isAccountLocked($admin->fresh()));
        // even the right password is refused while locked
        $this->postJson('api/resort/profile-change-password', ['old_password' => config('demo.password'), 'password' => 'NewPassw0rd!xyz', 'confirm_password' => 'NewPassw0rd!xyz'])
            ->assertJson(['success' => false]);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check(config('demo.password'), $admin->fresh()->password));
    }

    public function test_18_api_answers_carry_both_success_and_status()
    {
        $this->app_as('employee');
        // a `status`-style endpoint now also says `success`, and a `success`-style one also says `status`
        $this->postJson('api/resort/profile-change-password', [])->assertJsonStructure(['success', 'status']);
        $this->getJson('api/job-description')->assertJson(['success' => true, 'status' => true]);
    }

    public function test_9_holidays_carry_the_year_and_come_sorted()
    {
        DB::table('resortholidays')->where('resort_id', $this->resortId)->delete();
        foreach (['2026-12-31', '2025-12-31', '2026-01-01'] as $d) {
            DB::table('resortholidays')->insert(['resort_id' => $this->resortId, 'PublicHolidaydate' => $d, 'PublicHolidayName' => "H $d"]);
        }
        $this->app_as('employee');
        $rows = $this->getJson('api/calendar/get-holidays')->assertOk()->json('data');
        $this->assertSame(['2025-12-31', '2026-01-01', '2026-12-31'], array_column($rows, 'full_date'));
        $this->assertSame('31 Dec', $rows[0]['date']);   // old field kept for app builds already out
    }

    public function test_11_visa_categories_say_which_have_a_file_and_labels_are_clean()
    {
        $emp = $this->emp('employee');
        DB::table('visa_employee_expiry_data')->insert(['resort_id' => $this->resortId, 'employee_id' => $emp->id, 'DocumentName' => 'Passport_Copy',
            'Ai_extracted_data' => json_encode(['extracted_fields' => ['passport no.:' => 'P123', 'Date of Expiry' => '01/01/2030']])]);
        $this->app_as('employee');
        $has = $this->getJson('api/profile/visa-category')->assertOk()->json('has_data');
        $this->assertTrue($has['Passport_Copy']);
        $this->assertFalse($has['Visa']);
        $titles = array_column($this->getJson('api/profile/visa-data/Passport_Copy')->json('visa_data.Ai_extracted_data.extracted_fields'), 'Title');
        $this->assertSame(['Passport no.', 'Date of Expiry'], $titles);
    }

    public function test_login_picks_the_account_whose_password_matches_when_an_emp_id_is_shared()
    {
        $a = $this->emp('employee');
        $b = $this->emp('supervisor');
        DB::table('employees')->where('id', $b->id)->update(['Emp_id' => $a->Emp_id]);   // same ID, as across two resorts
        DB::table('resort_admins')->where('id', $b->Admin_Parent_id)->update(['password' => \Illuminate\Support\Facades\Hash::make('Other#Passw0rd26')]);
        $tokens = fn ($e) => DB::table('oauth_access_tokens')->where('user_id', $e->Admin_Parent_id)->count();
        [$ta, $tb] = [$tokens($a), $tokens($b)];

        $this->postJson('api/login', ['emp_id' => $a->Emp_id, 'password' => config('demo.password')])->assertJson(['success' => true]);
        $this->assertSame([$ta + 1, $tb], [$tokens($a), $tokens($b)]);
        $this->postJson('api/login', ['emp_id' => $a->Emp_id, 'password' => 'Other#Passw0rd26'])->assertJson(['success' => true]);
        $this->assertSame([$ta + 1, $tb + 1], [$tokens($a), $tokens($b)]);
        $this->postJson('api/login', ['emp_id' => $a->Emp_id, 'password' => 'wrong'])->assertJson(['success' => false]);
    }

    public function test_overtime_rows_can_only_be_acted_on_by_someone_allowed_to_approve_them()
    {
        $ids = collect(['employee', 'hod.kitchen', 'hr'])->map(fn ($l) => $this->emp($l)->id)
            ->push(Common::getSubordinates($this->emp('hod.kitchen')->id)[0]);   // one of the kitchen HOD's team
        foreach ($ids as $id) {
            DB::table('parent_attendaces')->insert(['resort_id' => $this->resortId, 'Emp_id' => $id, 'date' => now()->toDateString(),
                'OverTime' => '02:00', 'Status' => 'present', 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (['hod.kitchen', 'hr'] as $login) {
            $this->app_as($login);
            $me = $this->emp($login);
            $rows = $this->getJson('api/timeandattendance/time-attendance-hod-dashboard')->assertOk()->json('dashboard.pending_requests.ot_requests');
            $this->assertNotEmpty($rows, $login);
            foreach ($rows as $row) {
                // the flag is exactly the rule approve-reject-ot enforces (never own overtime)
                $this->assertSame(Common::canManageAttendanceFor($row['emp_id'], $me), $row['can_accept'], "$login / emp {$row['emp_id']}");
                if ((int) $row['emp_id'] === (int) $me->id) {
                    $this->assertFalse($row['can_accept']);
                }
            }
        }
    }

    public function test_15_a_pending_sos_says_it_is_waiting_for_approval()
    {
        $emp = $this->emp('employee');
        $id = DB::table('sos_history')->insertGetId(['resort_id' => $this->resortId, 'emergency_id' => 1, 'emp_initiated_by' => $emp->id,
            'location' => 'Jetty', 'latitude' => '4.17', 'longitude' => '73.5', 'date' => now()->toDateString(), 'time' => '10:00', 'status' => 'Pending']);
        $this->app_as('hr');
        $this->getJson('api/sos/employee-team-location/' . base64_encode((string) $id))
            ->assertOk()->assertJson(['success' => false, 'sos_status' => 'Pending']);
    }
}
