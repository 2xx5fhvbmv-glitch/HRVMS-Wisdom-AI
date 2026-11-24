<?php

namespace Tests\Feature;

use App\Helpers\StorageHelper;
use App\Models\ApplicantOfferContract;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** N18: offer letters / contracts are private; Task 2: public candidate links expire. */
class OfferContractPrivateFilesTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    private const PDF = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();   // fakes the StorageHelper disk
        Storage::fake('public');
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
    }

    private function otherResortId(): int
    {
        return (int) DB::table('resorts')->where('id', '!=', $this->resortId)->value('id');
    }

    private function applicant(int $resortId): int
    {
        return DB::table('applicant_form_data')->insertGetId([
            'resort_id' => $resortId, 'Parent_v_id' => 0, 'marital_status' => 'Single', 'terms_conditions' => 1, 'NotiesPeriod' => 0,
            'first_name' => 'Offer', 'last_name' => 'Candidate', 'email' => 'offer.candidate@example.test',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function offer(int $resortId, array $attrs = []): ApplicantOfferContract
    {
        $applicant = $this->applicant($resortId);
        return ApplicantOfferContract::create($attrs + [
            'applicant_id' => $applicant, 'applicant_status_id' => 0, 'resort_id' => $resortId,
            'type' => 'offer_letter', 'status' => 'Sent', 'token' => Str::uuid()->toString(),
            'file_path' => ApplicantOfferContract::storeFile($resortId, 'offer_letter', $applicant, self::PDF, 'pdf'),
            'token_expires_at' => ApplicantOfferContract::tokenExpiry(),
        ]);
    }

    public function test_sent_offer_letter_is_stored_privately_and_encrypted()
    {
        $applicant = $this->applicant($this->resortId);
        $statusId = DB::table('applicant_wise_statuses')->insertGetId(['Applicant_id' => $applicant, 'As_ApprovedBy' => 3, 'status' => 'Selected', 'created_at' => now(), 'updated_at' => now()]);
        $tmp = tempnam(sys_get_temp_dir(), 'of'); file_put_contents($tmp, self::PDF);

        $this->as('hr')->post(route('resort.ta.sendOfferLetter'), [
            'applicant_id' => base64_encode($applicant), 'applicant_status_id' => base64_encode($statusId),
            'offer_letter' => new UploadedFile($tmp, 'offer.pdf', 'application/pdf', null, true),
        ])->assertOk()->assertJson(['success' => true]);

        $row = ApplicantOfferContract::where('applicant_id', $applicant)->firstOrFail();
        $this->assertSame([], Storage::disk('public')->allFiles(), 'nothing may land on the public disk');
        $this->assertStringEndsWith('.pdf.enc', $row->file_path);
        $this->assertTrue(StorageHelper::exists($row->file_path));
        $this->assertStringNotContainsString('%PDF', StorageHelper::get($row->file_path));
        $this->assertSame(self::PDF, ApplicantOfferContract::readFile($row->file_path));
        $this->assertTrue($row->token_expires_at->between(now()->addDays(29), now()->addDays(31)));
    }

    public function test_token_download_needs_the_right_unexpired_token()
    {
        $offer = $this->offer($this->resortId);

        $res = $this->get(route('resort.offer.letter.download', $offer->token))->assertOk();
        $this->assertSame(self::PDF, $res->getContent());
        $res->assertHeader('Content-Type', 'application/pdf');
        $res->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('attachment; filename="Offer_Letter.pdf"', $res->headers->get('Content-Disposition'));

        $this->get(route('resort.offer.letter.download', Str::uuid()->toString()))->assertNotFound();
        // an offer token does not open the contract download
        $this->get(route('resort.contract.download', $offer->token))->assertNotFound();

        $offer->update(['token_expires_at' => now()->subMinute()]);
        $this->get(route('resort.offer.letter.download', $offer->token))->assertStatus(410)->assertSee(ApplicantOfferContract::EXPIRED_MESSAGE);
    }

    public function test_legacy_public_file_still_downloads()
    {
        $offer = $this->offer($this->resortId);
        Storage::disk('public')->put('offer_letters/' . $this->resortId . '/offer_letter_1_1.pdf', self::PDF);
        $offer->update(['file_path' => 'offer_letters/' . $this->resortId . '/offer_letter_1_1.pdf']);

        $this->assertSame(self::PDF, $this->get(route('resort.offer.letter.download', $offer->token))->assertOk()->getContent());
    }

    public function test_hr_download_is_resort_scoped_and_hr_only()
    {
        $mine = $this->offer($this->resortId);
        $foreign = $this->offer($this->otherResortId());

        $this->assertSame(self::PDF, $this->as('hr')->get(route('resort.ta.offerContract.download', $mine->id))->assertOk()->getContent());
        $this->as('hr')->get(route('resort.ta.offerContract.download', $foreign->id))->assertNotFound();
        $this->as('employee')->get(route('resort.ta.offerContract.download', $mine->id))->assertForbidden();
    }

    public function test_expired_links_are_refused()
    {
        $offer = $this->offer($this->resortId, ['token_expires_at' => now()->subDay()]);
        $this->get(route('resort.offer.letter.show', $offer->token))->assertStatus(410)->assertSee(ApplicantOfferContract::EXPIRED_MESSAGE);
        $this->post(route('resort.offer.letter.accept', $offer->token))->assertStatus(410);
        $this->assertSame('Sent', $offer->fresh()->status);

        $applicant = $this->applicant($this->resortId);
        DB::table('applicant_form_data')->where('id', $applicant)->update([
            'consent_token' => 'c-' . Str::uuid(), 'consent_token_expires_at' => now()->subDay(), 'consent_status' => 'pending',
            'availability_token' => 'a-' . Str::uuid(), 'availability_token_expires_at' => now()->subDay(), 'availability_status' => 'pending',
        ]);
        $row = DB::table('applicant_form_data')->find($applicant);
        $this->get(route('resort.consent.show', $row->consent_token))->assertStatus(410);
        $this->post(route('resort.consent.approve', $row->consent_token))->assertStatus(410);
        $this->get(route('resort.availability.show', $row->availability_token))->assertStatus(410);
        $this->post(route('resort.availability.available', $row->availability_token))->assertStatus(410);
        $row = DB::table('applicant_form_data')->find($applicant);
        $this->assertSame('pending', $row->consent_status);
        $this->assertSame('pending', $row->availability_status);

        $interviewToken = Str::uuid()->toString();
        $statusId = DB::table('applicant_wise_statuses')->insertGetId(['Applicant_id' => $applicant, 'As_ApprovedBy' => 3, 'status' => 'Sortlisted', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('applicant_inter_view_details')->insert([
            'resort_id' => $this->resortId, 'Applicant_id' => $applicant, 'ApplicantStatus_id' => $statusId, 'Status' => 'Invitation Sent', 'InterViewDate' => now()->addDays(3)->toDateString(),
            'invitation_token' => $interviewToken, 'token_expires_at' => now()->subDay(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->get(route('resort.interview.invitation.show', $interviewToken))->assertStatus(410);
        $this->post(route('resort.interview.invitation.accept', $interviewToken))->assertStatus(410);
        $this->assertSame('Invitation Sent', DB::table('applicant_inter_view_details')->where('invitation_token', $interviewToken)->value('Status'));

        // an unexpired link still opens
        $fresh = $this->offer($this->resortId);
        $this->get(route('resort.offer.letter.show', $fresh->token))->assertOk()->assertSee(route('resort.offer.letter.download', $fresh->token), false);
    }
}
