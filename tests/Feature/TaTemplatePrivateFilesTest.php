<?php

namespace Tests\Feature;

use App\Helpers\StorageHelper;
use App\Models\ApplicantOfferContract;
use App\Models\TaDocumentTemplate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** TA offer/contract blank templates are private + encrypted; legacy public rows keep working. */
class TaTemplatePrivateFilesTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    private string $docx;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();   // fakes the StorageHelper disk
        Storage::fake('public');
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
        $this->docx = file_get_contents(public_path('sample/document_filed.docx'));
    }

    private function otherResortId(): int
    {
        return (int) DB::table('resorts')->where('id', '!=', $this->resortId)->value('id');
    }

    private function upload(string $bytes, string $name = 'offer.docx'): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'tpl');
        file_put_contents($tmp, $bytes);
        return new UploadedFile($tmp, $name, null, null, true);
    }

    private function template(int $resortId, ?string $path = null): TaDocumentTemplate
    {
        return TaDocumentTemplate::create([
            'resort_id' => $resortId, 'type' => 'offer_letter', 'name' => 'Std Offer', 'is_default' => true,
            'file_path' => $path ?? TaDocumentTemplate::storeFile($resortId, 'offer_letter', $this->docx),
        ]);
    }

    public function test_uploaded_template_is_stored_privately_and_encrypted()
    {
        $this->as('hr')->post(route('resort.ta.documentTemplate.store'), [
            'type' => 'offer_letter', 'name' => 'Std Offer', 'template_file' => $this->upload($this->docx),
        ])->assertOk()->assertJson(['success' => true]);

        $row = TaDocumentTemplate::where('resort_id', $this->resortId)->where('name', 'Std Offer')->latest('id')->firstOrFail();
        $this->assertSame([], Storage::disk('public')->allFiles(), 'nothing may land on the public disk');
        $this->assertStringEndsWith('.docx.enc', $row->file_path);
        $this->assertStringContainsString('/private/talent_acquisition/templates/', $row->file_path);
        $this->assertTrue(StorageHelper::exists($row->file_path));
        $this->assertStringStartsNotWith('PK', StorageHelper::get($row->file_path));
        $this->assertSame($this->docx, TaDocumentTemplate::readFile($row->file_path));
    }

    public function test_non_docx_upload_is_refused()
    {
        $this->as('hr')->post(route('resort.ta.documentTemplate.store'), [
            'type' => 'offer_letter', 'name' => 'Fake', 'template_file' => $this->upload("<?php echo 1;", 'evil.docx'),
        ])->assertStatus(422);
        $this->as('hr')->post(route('resort.ta.documentTemplate.store'), [
            'type' => 'offer_letter', 'name' => 'Fake', 'template_file' => $this->upload($this->docx, 'offer.pdf'),
        ])->assertStatus(422);
        $this->assertFalse(TaDocumentTemplate::where('name', 'Fake')->exists());
    }

    public function test_hr_download_is_resort_scoped_and_hr_only()
    {
        $mine = $this->template($this->resortId);
        $foreign = $this->template($this->otherResortId());

        $res = $this->as('hr')->get(route('resort.ta.documentTemplate.download', $mine->id))->assertOk();
        $this->assertSame($this->docx, $res->getContent());
        $res->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('attachment; filename="Std_Offer.docx"', $res->headers->get('Content-Disposition'));

        $this->as('hr')->get(route('resort.ta.documentTemplate.download', $foreign->id))->assertNotFound();
        $this->as('employee')->get(route('resort.ta.documentTemplate.download', $mine->id))->assertForbidden();
    }

    public function test_list_links_to_the_private_route_not_a_public_url()
    {
        $tpl = $this->template($this->resortId);
        $json = $this->as('hr')->getJson(route('resort.ta.offerLetterTemplates.list'))->assertOk()->getContent();
        $this->assertStringContainsString(str_replace('/', '\/', route('resort.ta.documentTemplate.download', $tpl->id)), $json);
        $this->assertStringNotContainsString('storage\/', $json);
    }

    public function test_legacy_public_template_still_downloads_and_fills_an_offer()
    {
        $legacy = 'ta_templates/' . $this->resortId . '/offer_letter_1_1.docx';
        Storage::disk('public')->put($legacy, $this->docx);
        $tpl = $this->template($this->resortId, $legacy);

        $this->assertSame($this->docx, $this->as('hr')->get(route('resort.ta.documentTemplate.download', $tpl->id))->assertOk()->getContent());

        $applicant = DB::table('applicant_form_data')->insertGetId([
            'resort_id' => $this->resortId, 'Parent_v_id' => 0, 'marital_status' => 'Single', 'terms_conditions' => 1, 'NotiesPeriod' => 0,
            'first_name' => 'Tpl', 'last_name' => 'Candidate', 'email' => 'tpl.candidate@example.test',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $statusId = DB::table('applicant_wise_statuses')->insertGetId(['Applicant_id' => $applicant, 'As_ApprovedBy' => 3, 'status' => 'Selected', 'created_at' => now(), 'updated_at' => now()]);

        $this->as('hr')->post(route('resort.ta.sendOfferLetter'), [
            'applicant_id' => base64_encode($applicant), 'applicant_status_id' => base64_encode($statusId), 'template_id' => $tpl->id,
        ])->assertOk()->assertJson(['success' => true]);

        $offer = ApplicantOfferContract::where('applicant_id', $applicant)->firstOrFail();
        $this->assertSame($tpl->id, (int) $offer->ta_document_template_id);
        $this->assertStringEndsWith('.docx.enc', $offer->file_path);
        $this->assertStringStartsWith('PK', ApplicantOfferContract::readFile($offer->file_path));
    }

    public function test_move_command_dry_run_lists_then_real_run_moves()
    {
        $legacy = 'ta_templates/' . $this->resortId . '/contract_1_1.docx';
        Storage::disk('public')->put($legacy, $this->docx);
        $tpl = $this->template($this->resortId, $legacy);

        $this->artisan('ta:move-templates', ['--dry-run' => true])->expectsOutputToContain($legacy)->assertSuccessful();
        $this->assertSame($legacy, $tpl->fresh()->file_path);
        $this->assertTrue(Storage::disk('public')->exists($legacy));

        // Disks are faked here, so the real run is safe in the test.
        $this->artisan('ta:move-templates')->assertSuccessful();
        $moved = $tpl->fresh()->file_path;
        $this->assertStringEndsWith('.docx.enc', $moved);
        $this->assertFalse(Storage::disk('public')->exists($legacy));
        $this->assertSame($this->docx, TaDocumentTemplate::readFile($moved));
    }
}
