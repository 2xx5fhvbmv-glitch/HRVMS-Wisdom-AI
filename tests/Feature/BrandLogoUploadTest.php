<?php

namespace Tests\Feature;

use App\Helpers\StorageHelper;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** Site settings › logo: a logo that did not reach storage is reported, never saved as the resort's logo. */
class BrandLogoUploadTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        config(['settings.storage_driver' => 'wasabi']);
    }

    private function save()
    {
        (new \ReflectionClass(StorageHelper::class))->setStaticPropertyValue('cachedDisk', null);
        (new \ReflectionClass(StorageHelper::class))->setStaticPropertyValue('cachedDriver', null);
        return $this->as('admin')->post(route('resort.updatesitesettings'), [
            'resort_id' => $this->resortId, 'currency' => 'Dollar', 'DollertoMVR' => '15.42', 'MVRtoDoller' => '0.0648', 'FinalApproval' => 8,
            'profile_picture' => UploadedFile::fake()->image('logo.png', 120, 120),
        ], ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']);
    }

    public function test_a_stored_logo_is_saved_and_a_failed_one_is_reported()
    {
        $before = DB::table('resorts')->where('id', $this->resortId)->value('logo');

        // the bucket refuses the write (putFileAs returns false, nothing stored)
        $disk = \Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);
        $disk->shouldReceive('put')->andReturn(false);
        $disk->shouldReceive('exists')->andReturn(false);
        Storage::set('wasabi', $disk);
        $this->save()->assertStatus(500)->assertJson(['success' => false]);
        $this->assertSame($before, DB::table('resorts')->where('id', $this->resortId)->value('logo'));

        // the write works
        (new \ReflectionClass(StorageHelper::class))->setStaticPropertyValue('cachedDisk', null);
        Storage::set('wasabi', Storage::createLocalDriver(['root' => storage_path('framework/testing/disks/wasabi')]));
        $this->save()->assertOk()->assertJson(['success' => true]);
        $logo = DB::table('resorts')->where('id', $this->resortId)->value('logo');
        $this->assertNotSame($before, $logo);
        $this->assertTrue(Storage::disk('wasabi')->exists(config('settings.brand_logo_folder') . '/' . $logo));
    }
}
