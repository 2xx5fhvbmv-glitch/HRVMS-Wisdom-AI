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

/** Backend fixes for the mobile-app QA report (2026-11-18): items 1, 3, 5, 6, 8, 12, 13, 17, 18. */
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
}
