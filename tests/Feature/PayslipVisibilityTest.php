<?php

namespace Tests\Feature;

use App\Helpers\StorageHelper;
use App\Models\Employee;
use App\Support\Demo\Demo;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * G2 (founder, 2026-11-06): a payslip — app or web — only ever comes from a locked run. The demo has 12 locked
 * runs and last month's run waiting on Finance → HR → GM; that pending run must not show as a payslip anywhere,
 * and the app says "still being processed" instead.
 */
class PayslipVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    private int $resortId;
    private object $run;
    private Employee $emp;
    private Carbon $pending;
    private Carbon $locked;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'demo.enabled' => true, 'demo.password' => 'Demo#Pass2026', 'demo.resort_code' => 'DEMOTEST97',
            'demo.resort_email' => 'resort@demo.thewisdom.ai', 'demo.prefix' => 'DMP']);
        Demo::refresh();
        Storage::fake(config('settings.storage_driver'));
        (new \ReflectionClass(StorageHelper::class))->setStaticPropertyValue('cachedDisk', null);
        Mail::fake();
        Http::fake(['api.aladhan.com/*' => Http::response(['data' => ['hijri' => ['month' => ['number' => 3]]]])]);
        $this->artisan('demo:reset', ['--force' => true])->assertSuccessful();
        Demo::refresh();
        $this->resortId = Demo::resortId();
        $this->run = DB::table('payroll')->where('resort_id', $this->resortId)->where('payroll_category', 'Permanent')->where('status', 'pending_approval')->first();
        $this->pending = Carbon::parse($this->run->end_date);
        $this->locked = $this->pending->copy()->subMonthNoOverflow();
        $this->assertSame('locked', DB::table('payroll')->where('resort_id', $this->resortId)->where('payroll_category', 'Permanent')
            ->whereMonth('end_date', $this->locked->month)->whereYear('end_date', $this->locked->year)->value('status'));
        $this->emp = Employee::whereIn('id', DB::table('payroll_reviews')->where('payroll_id', $this->run->id)->select('employee_id'))
            ->where('employment_type', 'Full-Time')->whereHas('resortAdmin')->firstOrFail();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    /** Controllers read the user in their constructor and Laravel keeps the instance on the route — drop it. */
    private function app_as(Employee $e): void
    {
        app('auth')->forgetGuards();
        foreach (app('router')->getRoutes()->getRoutes() as $route) {
            $route->flushController();
        }
        Passport::actingAs($e->resortAdmin, [], 'api');
    }

    private function details(Carbon $period, array $extra = [])
    {
        return $this->postJson('/api/payroll/payslip-details', ['month' => $period->month, 'year' => $period->year] + $extra);
    }

    public function test_the_app_shows_no_payslip_for_a_run_that_is_not_locked()
    {
        $this->app_as($this->emp);

        $this->details($this->pending)->assertOk()->assertJson(['success' => false]);
        $this->details($this->locked)->assertOk()->assertJson(['success' => true]);
        $this->postJson('/api/payroll/payslip-pdf-download', ['month' => $this->pending->month, 'year' => $this->pending->year])->assertJson(['success' => false]);
        $this->postJson('/api/payroll/payslip-pdf-download', ['month' => $this->locked->month, 'year' => $this->locked->year])->assertJson(['success' => true]);

        $list = $this->postJson('/api/payroll/payslip-list', ['year' => $this->pending->year])->assertOk()->json('payslip_data');
        $this->assertNotContains($this->run->id, array_column($list['payslip_list_data'], 'id'));
        $this->assertNotSame($this->run->id, $list['payroll_net_sal_ot']['id'], 'the header snapshot is the latest locked run');
        $this->assertSame("This month's payroll is still being processed.", $list['current_period_message']);

        $dash = $this->getJson('/api/payroll/payroll-dashboard?year=' . $this->pending->year)->assertOk()->json('payroll_data');
        $this->assertNotSame($this->run->start_date, $dash['payslip_details']['payslip_start_date']);
        $this->assertSame(0, $dash['chart']['basic_pay'][Carbon::parse($this->run->start_date)->month - 1], 'pending month not in the chart');
        $this->assertSame("This month's payroll is still being processed.", $dash['current_period_message']);
    }

    public function test_once_locked_the_payslip_appears_and_the_message_goes()
    {
        DB::table('payroll')->where('id', $this->run->id)->update(['status' => 'locked']);
        $this->app_as($this->emp);

        $this->details($this->pending)->assertJson(['success' => true]);
        $this->assertNull($this->postJson('/api/payroll/payslip-list', ['year' => $this->pending->year])->json('payslip_data.current_period_message'));
        $this->assertSame($this->run->start_date, $this->getJson('/api/payroll/payroll-dashboard?year=' . $this->pending->year)
            ->json('payroll_data.payslip_details.payslip_start_date'));
    }

    public function test_the_app_only_ever_shows_your_own_payslip()
    {
        $this->app_as($this->emp);
        $other = Employee::where('resort_id', $this->resortId)->where('id', '!=', $this->emp->id)->value('id');
        $this->details($this->locked, ['employee_id' => $other])->assertJsonPath('payslip_data.employee.Emp_id', $this->emp->Emp_id);
    }

    public function test_the_web_payslip_needs_a_locked_run_in_this_resort()
    {
        auth('resort-admin')->logout();
        $this->post(route('resort.login'), ['email' => 'finance@demo.thewisdom.ai', 'password' => 'Demo#Pass2026'])->assertOk();
        $view = fn ($emp, Carbon $p) => $this->postJson(route('payroll.payslip.view'), ['employee_id' => $emp, 'month' => $p->month, 'year' => $p->year]);

        $view($this->emp->id, $this->pending)->assertJson(['success' => false]);
        $view($this->emp->id, $this->locked)->assertJson(['success' => true]);
        $this->get(route('payslip.show'))->assertOk();
        $this->postJson(route('payroll.payslip.share'), ['employee_id' => $this->emp->id, 'month' => $this->pending->month, 'year' => $this->pending->year])
            ->assertJson(['success' => false]);

        $elsewhere = Employee::where('resort_id', '!=', $this->resortId)->value('id');
        $view($elsewhere, $this->locked)->assertJson(['success' => false]);
    }
}
