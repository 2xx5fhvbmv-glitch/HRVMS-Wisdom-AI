<?php

namespace Tests\Feature;

use App\Helpers\Common;
use App\Helpers\StorageHelper;
use App\Models\LetterheadSetting;
use App\Models\TemporaryClinicDoctor;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/**
 * Letterhead images live on the private StorageHelper disk and reach PDFs as data: URIs;
 * the temporary clinic doctor login locks the account after repeated wrong passwords.
 */
class LetterheadAndClinicLockoutTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    public function test_a_clinic_doctor_is_locked_after_five_wrong_passwords()
    {
        Mail::fake();
        $this->withoutMiddleware(ThrottleRequests::class);
        $doctor = TemporaryClinicDoctor::create([
            'resort_id' => (int) DB::table('resorts')->value('id'),
            'name' => 'Lockout Test', 'email' => 'lockout-test-' . uniqid() . '@example.test',
            'password' => Hash::make('Right#Pass1'), 'status' => 'Active',
        ]);
        $login = fn ($password) => $this->postJson('/api/clinic-doctor/login', ['email' => $doctor->email, 'password' => $password]);

        for ($i = 0; $i < Common::LOGIN_LOCKOUT_THRESHOLD; $i++) {
            $login('wrong')->assertJson(['success' => false, 'message' => 'Invalid email or password.']);
        }
        $this->assertTrue(Common::isAccountLocked($doctor->fresh()));

        $login('Right#Pass1')->assertJson(['success' => false])->assertJsonMissing(['token']);
        $this->assertStringContainsString('Too many failed', $login('Right#Pass1')->json('message'));
    }

    public function test_an_unmoved_letterhead_still_reads_the_old_public_file()
    {
        $rel = 'uploads/letterheads/test-' . uniqid() . '/header.png';
        @mkdir(dirname(public_path($rel)), 0755, true);
        file_put_contents(public_path($rel), UploadedFile::fake()->image('h.png', 10, 10)->getContent());
        try {
            $uri = (new LetterheadSetting(['header_image' => $rel]))->imageDataUri('header_image');
            $this->assertStringStartsWith('data:image/png;base64,', $uri);
        } finally {
            @unlink(public_path($rel));
            @rmdir(dirname(public_path($rel)));
        }
    }

    public function test_letterhead_upload_goes_to_private_storage_and_pdfs_embed_it()
    {
        $this->buildDemoOrganisation();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $this->as('admin')->post(route('people.config.letterhead.store'), [
            'header_image' => UploadedFile::fake()->image('header.png', 200, 40),
            'signatory_name' => 'Jane Doe',
        ], ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->assertOk()->assertJson(['status' => 'success']);

        $path = LetterheadSetting::where('resort_id', $this->resortId)->value('header_image');
        $this->assertStringStartsWith('letterheads/' . $this->resortId . '/header_image_', $path);
        $this->assertStringEndsWith('.png', $path);
        $this->assertTrue(Storage::disk(config('settings.storage_driver'))->exists($path));
        $this->assertFileDoesNotExist(public_path($path));
        $this->assertFileDoesNotExist(public_path('uploads/letterheads/' . $this->resortId . '/' . basename($path)));

        $letterhead = Common::getLetterheadData($this->resortId);
        $this->assertStringStartsWith('data:image/png;base64,', $letterhead['headerImage']);
        $html = view('resorts.people.probation.probation_letter_pdf', [
            'letterContent' => '<p>Body</p>', 'letterhead' => $letterhead,
            'resort' => DB::table('resorts')->where('id', $this->resortId)->first(), 'resortLogo' => null,
            'signatureImage' => $letterhead['signatureImage'], 'signatoryName' => 'Jane Doe', 'signatoryTitle' => 'HR',
        ])->render();
        $this->assertStringContainsString('src="data:image/png;base64,', $html);

        // the settings page previews it inline, not through a public URL
        $this->as('admin')->get(route('people.config.letterhead'))->assertOk()
            ->assertSee('data:image/png;base64,', false)->assertDontSee('uploads/letterheads', false);
    }
}
