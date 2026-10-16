<?php

namespace Tests\Feature;

use App\Helpers\StorageHelper;
use App\Support\Demo\Demo;
use App\Support\Demo\DemoVerifier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Demo ENV slice 3b (leave and island passes): every demo check passes, and a
 * seeded leave waiting on the GM is approved through the real route, which
 * writes the attendance the same way the seeder wrote the other approved leave.
 */
class DemoLeaveTest extends TestCase
{
    use DatabaseTransactions;

    private int $resortId;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'demo.enabled' => true, 'demo.password' => 'Demo#Pass2026', 'demo.resort_code' => 'DEMOTEST99',
            'demo.resort_email' => 'resort@demo.thewisdom.ai', 'demo.prefix' => 'DMT']);
        Demo::refresh();
        Storage::fake(config('settings.storage_driver'));
        (new \ReflectionClass(StorageHelper::class))->setStaticPropertyValue('cachedDisk', null);
        Mail::fake();
        Http::fake(['api.aladhan.com/*' => Http::response(['data' => ['hijri' => ['month' => ['number' => 3]]]])]);
        $this->artisan('demo:reset', ['--force' => true])->assertSuccessful();
        Demo::refresh();
        $this->resortId = Demo::resortId();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_every_demo_check_passes()
    {
        $failed = collect((new DemoVerifier)->run()['checks'])->reject(fn ($c) => $c['ok'])->map(fn ($c) => "{$c['name']}: {$c['detail']}");
        $this->assertSame([], $failed->values()->all());
    }

    public function test_the_gm_approves_a_waiting_leave_and_its_days_become_leave()
    {
        $gm = DB::table('employees')->where('resort_id', $this->resortId)->where('rank', 8)->first();
        $leave = DB::table('employees_leaves as l')->join('employees_leaves_status as s', 's.leave_request_id', '=', 'l.id')
            ->where('l.resort_id', $this->resortId)->where('l.status', 'Pending')->where('s.approver_id', $gm->id)->where('s.status', 'Pending')
            ->whereNotExists(fn ($q) => $q->from('employees_leaves_status as o')->whereColumn('o.leave_request_id', 'l.id')
                ->where('o.status', 'Pending')->where('o.approver_id', '!=', $gm->id))
            ->select('l.*')->first();
        $this->assertNotNull($leave, 'a leave is waiting on the GM alone');

        $this->post(route('resort.login'), ['email' => 'gm@demo.thewisdom.ai', 'password' => 'Demo#Pass2026'])->assertOk();
        $this->post(route('leave.handleAction'), ['leave_id' => $leave->id, 'action' => 'Approved'])->assertOk();

        $this->assertSame('Approved', DB::table('employees_leaves')->where('id', $leave->id)->value('status'));
        // The app's own write-through, compared with what the seeder wrote for the other approved leave.
        $marked = DB::table('parent_attendaces')->where('Emp_id', $leave->emp_id)->whereBetween('date', [$leave->from_date, $leave->to_date])->get();
        $this->assertNotEmpty($marked);
        $seeded = DB::table('parent_attendaces')->where('resort_id', $this->resortId)->where('Status', 'FullDayLeave')
            ->where('Emp_id', '!=', $leave->emp_id)->first();
        foreach (['Status', 'CheckInCheckOut_Type'] as $column) {
            $this->assertSame([$seeded->$column], $marked->pluck($column)->unique()->values()->all(), $column);
        }
    }
}
