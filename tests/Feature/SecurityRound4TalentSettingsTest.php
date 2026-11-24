<?php

namespace Tests\Feature;

use App\Helpers\Common;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** Security audit run-4: N2, N3, N5, N8, N9, N10, N17. */
class SecurityRound4TalentSettingsTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
    }

    private function otherResortId(): int
    {
        return (int) DB::table('resorts')->where('id', '!=', $this->resortId)->value('id');
    }

    private function applicant(int $resortId, string $first = 'Test'): int
    {
        return DB::table('applicant_form_data')->insertGetId([
            'resort_id' => $resortId, 'Parent_v_id' => 0, 'marital_status' => 'Single', 'terms_conditions' => 1, 'NotiesPeriod' => 0,
            'first_name' => $first, 'last_name' => 'Applicant', 'email' => strtolower($first) . '@example.test',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function untick(string $login): void
    {
        $emp = $this->emp($login);
        DB::table('resort_interal_pages_permissions')->where('resort_id', $this->resortId)->where('position_id', $emp->Position_id)->delete();
        Common::forgetAllowedRoutes($this->resortId, $emp->Position_id);
    }

    // N2
    public function test_casual_attendance_mark_needs_hr_or_the_department_head_and_never_self()
    {
        $target = $this->emp('employee');
        $url = route('resort.timeandattendance.nonpermanent.mark');

        $this->as('supervisor')->postJson($url, ['emp_id' => $target->id, 'status' => 'Absent'])->assertStatus(403);
        $hr = $this->emp('hr');
        $this->as('hr')->postJson($url, ['emp_id' => $hr->id, 'status' => 'Present'])->assertStatus(403);
        $this->assertFalse(DB::table('parent_attendaces')->whereIn('Emp_id', [$target->id, $hr->id])->whereDate('date', now()->toDateString())->exists());
    }

    // N3
    public function test_applicant_status_change_is_resort_scoped_and_needs_hr_or_the_interviewer()
    {
        $mine = $this->applicant($this->resortId, 'Mine');
        $foreign = $this->applicant($this->otherResortId(), 'Foreign');
        $statusId = DB::table('applicant_wise_statuses')->insertGetId(['Applicant_id' => $mine, 'As_ApprovedBy' => 3, 'status' => 'Sortlisted', 'created_at' => now(), 'updated_at' => now()]);
        $url = route('resort.ta.ApprovedOrSortApplicantWiseStatus');
        $body = ['ApplicantID' => base64_encode($mine), 'applicantstatusid' => $statusId, 'Rank' => 'Round', 'interviewRound' => 'HRShortlisted'];

        // a line worker who is not the interviewer
        $this->as('employee')->postJson($url, $body)->assertStatus(403);
        // HR pointing the status row at another resort's applicant
        $this->as('hr')->postJson($url, ['ApplicantID' => base64_encode($foreign)] + $body)->assertStatus(404);
        // HR using another resort's email template
        $tpl = DB::table('ta_email_templates')->insertGetId(['Resort_id' => $this->otherResortId(), 'TempleteName' => 'x', 'MailSubject' => 'x', 'MailTemplete' => 'x', 'created_at' => now(), 'updated_at' => now()]);
        $this->as('hr')->postJson($url, $body + ['emailTemplateID' => $tpl])->assertStatus(404);
        $this->assertSame('Sortlisted', DB::table('applicant_wise_statuses')->where('id', $statusId)->value('status'));
        $this->assertSame($mine, (int) DB::table('applicant_wise_statuses')->where('id', $statusId)->value('Applicant_id'));

        // the assigned interviewer may move it on
        $interviewer = \App\Models\ResortAdmin::where('email', 'employee@demo.thewisdom.ai')->firstOrFail();
        DB::table('applicant_inter_view_details')->insert(['resort_id' => $this->resortId, 'Applicant_id' => $mine, 'ApplicantStatus_id' => $statusId,
            'InterViewDate' => now()->toDateString(), 'interviewer_id' => $interviewer->id, 'created_at' => now(), 'updated_at' => now()]);
        $this->as('employee')->postJson($url, $body)->assertOk();
        $this->assertSame('Round', DB::table('applicant_wise_statuses')->where('id', $statusId)->value('status'));
    }

    // N5
    public function test_talent_pool_on_the_ta_dashboard_shows_only_this_resorts_applicants()
    {
        $foreign = $this->applicant($this->otherResortId(), 'Outsider');
        DB::table('applicant_wise_statuses')->insert(['Applicant_id' => $foreign, 'As_ApprovedBy' => 3, 'status' => 'Rejected', 'created_at' => now()->addMinute(), 'updated_at' => now()]);

        $this->as('hr')->get(route('resort.recruitement.hrdashboard'))
            ->assertViewHas('talentPool', fn ($pool) => !collect($pool)->pluck('id')->contains($foreign));
    }

    // N8
    public function test_compliance_dismiss_is_post_only_and_hr_only()
    {
        $id = DB::table('compliances')->insertGetId(['resort_id' => $this->resortId, 'module_name' => 'People', 'compliance_breached_name' => 'Test breach',
            'Dismissal_status' => 'Pending', 'created_at' => now(), 'updated_at' => now()]);
        $url = route('people.compliances.dismiss', base64_encode($id));

        // GET is no longer a route: this app turns a wrong-method web request into a redirect (Handler), never a dismiss
        $this->assertNotSame(200, $this->as('hr')->get($url)->status());
        $this->assertSame('Pending', DB::table('compliances')->where('id', $id)->value('Dismissal_status'));
        $this->as('employee')->postJson($url)->assertStatus(403);
        $this->as('hod.kitchen')->postJson($url)->assertStatus(403);
        $this->assertSame('Pending', DB::table('compliances')->where('id', $id)->value('Dismissal_status'));

        $this->as('hr')->postJson($url)->assertOk();
        $this->assertSame('Rejected', DB::table('compliances')->where('id', $id)->value('Dismissal_status'));
        $this->as('employee')->postJson(route('people.compliances.restore', base64_encode($id)))->assertStatus(403);
    }

    // N9
    public function test_resort_currency_needs_settings_permission_and_a_known_value()
    {
        DB::table('resort_site_settings')->updateOrInsert(['resort_id' => $this->resortId], ['currency' => 'Dollar']);
        $url = route('sitesetting.UpdateCurrency');

        $this->untick('employee');
        $this->as('employee')->postJson($url, ['currency' => 'MVR'])->assertStatus(403);
        $this->as('hr')->postJson($url, ['currency' => '<script>'])->assertStatus(400);
        $this->assertSame('Dollar', DB::table('resort_site_settings')->where('resort_id', $this->resortId)->value('currency'));
        $this->as('hr')->postJson($url, ['currency' => 'MVR'])->assertOk()->assertJson(['success' => true]);
    }

    // N10
    public function test_notifications_can_only_be_deleted_or_marked_by_their_owner()
    {
        $row = fn ($empId) => DB::table('resort_notifications')->insertGetId(['resort_id' => $this->resortId, 'user_id' => $empId, 'module' => 'Test',
            'type' => 'Test', 'message' => 'hello', 'status' => 'unread', 'created_at' => now(), 'updated_at' => now()]);
        $theirs = $row($this->emp('employee')->id);
        $mine = $row($this->emp('hr')->id);

        $this->as('hr')->deleteJson(route('resort.Mark.NotificationDestory', base64_encode($theirs)))->assertStatus(404);
        $this->as('hr')->postJson(route('resort.Mark.Notification'), ['id' => $theirs])->assertJson(['success' => false]);
        $this->assertSame('unread', DB::table('resort_notifications')->where('id', $theirs)->value('status'));

        $this->as('hr')->deleteJson(route('resort.Mark.NotificationDestory', base64_encode($mine)))->assertOk();
        $this->assertFalse(DB::table('resort_notifications')->where('id', $mine)->exists());
    }

    // N17
    public function test_letterhead_and_offer_uploads_refuse_svg_and_html()
    {
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $this->as('hr')->post(route('people.config.letterhead.store'), ['header_image' => $svg])->assertStatus(422);

        $im = imagecreatetruecolor(10, 10); ob_start(); imagepng($im); $bytes = ob_get_clean();
        // A real temp file so its type is sniffed from content (fake uploads report the type from the name)
        $tmp = tempnam(sys_get_temp_dir(), 'lh'); file_put_contents($tmp, $bytes);
        $png = new UploadedFile($tmp, 'logo.html', null, null, true); // real PNG bytes, hostile name
        $this->as('hr')->post(route('people.config.letterhead.store'), ['header_image' => $png])->assertOk();
        $stored = DB::table('letterhead_settings')->where('resort_id', $this->resortId)->value('header_image');
        $this->assertStringEndsWith('.png', $stored);
        @unlink(public_path($stored));

        $applicant = $this->applicant($this->resortId);
        $tmpHtml = tempnam(sys_get_temp_dir(), 'of'); file_put_contents($tmpHtml, '<html><body><script>alert(1)</script></body></html>');
        $html = new UploadedFile($tmpHtml, 'offer.pdf', null, null, true); // HTML bytes posing as a PDF
        $this->as('hr')->post(route('resort.ta.sendOfferLetter'), ['applicant_id' => base64_encode($applicant), 'offer_letter' => $html])->assertStatus(422);
        $this->as('hr')->post(route('resort.ta.sendContract'), ['applicant_id' => base64_encode($applicant), 'contract_file' => $html])->assertStatus(422);
        $this->assertFalse(DB::table('applicant_offer_contracts')->where('applicant_id', $applicant)->exists());
    }
}
