<?php

namespace Tests\Feature;

use App\Helpers\StorageHelper;
use App\Support\Demo\Demo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Demo ENV module seeders: signed in as HR (and the GM / a HOD where the page is theirs), every inner
 * list the client opens — Talent Acquisition, People, exit & settlement, Performance, Accommodation,
 * Incidents, Surveys — answers with rows, the way its page's DataTable asks for them.
 */
class DemoModulesTest extends TestCase
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
        Http::fake(['api.aladhan.com/*' => Http::response(['data' => ['hijri' => ['month' => ['number' => 3]]]]), '*' => Http::response()]);
        $this->artisan('demo:reset', ['--force' => true])->assertSuccessful();
        Demo::refresh();
        $this->resortId = Demo::resortId();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    private function signIn(string $login): void
    {
        \Illuminate\Support\Facades\Auth::guard('resort-admin')->logout();
        $this->flushSession();
        $this->post(route('resort.login'), ['email' => "{$login}@demo.thewisdom.ai", 'password' => 'Demo#Pass2026'])->assertOk();
    }

    /** Each DataTable endpoint, called as its page calls it, returns at least $min rows. */
    private function assertRows(array $endpoints): void
    {
        $empty = [];
        foreach ($endpoints as [$route, $params, $min]) {
            $res = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])->get(route($route, $params));
            $rows = $res->isOk() ? count($res->json('data') ?? []) : -$res->getStatusCode();
            if ($rows < $min) {
                $empty[] = "{$route}: {$rows} rows";
            }
        }
        $this->assertSame([], $empty, 'Lists without their seeded rows');
    }

    public function test_hr_sees_every_module_list_filled()
    {
        $this->signIn('hr');
        $this->assertRows([
            // Talent Acquisition
            ['resort.ta.GetAllVacancies', [], 1], ['resort.ta.alltodolist', [], 1], ['resort.vacancies.FreshApplicant', [], 3],
            ['resort.ta.shortlistedapplicants', [], 3], ['resort.ta.UpcomingApplicants', [], 1], ['resort.ta.GetRejectedApplicants', [], 3],
            ['resort.ta.TalentPool', [], 3], ['resort.ta.getResortWiseQuestion', [], 1], ['resort.ta.jobdescription.GetJobDescList', ['compliance_status' => 'Approved'], 3],
            ['interview-assessment.list', [], 3], ['offline-interview.index', [], 3], ['resort.ta.get.source', [], 4], ['resort.ta.get.provider', [], 1], ['resort.ta.jobadvertisment.getList', [], 1],
            // People
            ['people.promotion.list', [], 5], ['people.promotion.history', [], 3], ['people.transfer.list', [], 2], ['people.transfer.history', [], 5],
            ['people.salary-increment.summary-list', [], 3], ['people.salary-increment.history-list', [], 3], ['people.announcements', [], 5],
            ['people.employee-resignation.index', [], 4], ['people.exit-clearance.list', [], 4], ['people.onboarding.itinerary.list', [], 3],
            ['people.probation', [], 1], ['people.compliance.list', [], 5],
            // Payroll: leavers
            ['payroll.pension.former-employees', [], 2], ['final.settlement.getdata', [], 2],
            // Performance
            ['Performance.employees.listData', [], 50], ['Performance.kpi.KpiList', [], 3], ['Performance.MonltyCheckIn', [], 3],
            ['Performance.Meeting.listData', [], 2], ['Performance.MonltyCheckIn.historyData', [], 3],
            // Accommodation
            ['resort.accommodation.HrGetMaintananceRequest', [], 1], ['resort.accommodation.EmpAccommodationList', [], 50],
            ['resort.accommodation.config.BuildingIndex', [], 3], ['resort.accommodation.HousekeepingRequestList', [], 3],
            ['resort.accommodation.MaintanaceRequestlist', [], 3], ['resort.accommodation.InventoryManagement', [], 3],
            // Incidents
            ['incident.list', [], 5], ['incident.resolved.list', [], 2], ['incident.meeting.list', [], 3], ['incident.pending-approvals', [], 1],
            // Surveys
            ['Survey.Surveylist', [], 1], ['Survey.CompleteSurvey', [], 1], ['Survey.DarftSurvey', [], 1],
        ]);
        // Info-update requests come back as rendered rows, pending ones first.
        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->get(route('people.info-update.list'))->assertOk()->assertSee('Personal Information');
        $this->get(route('resort.accommodation.release-decisions'))->assertOk()->assertSee('Yes, vacate');
        $this->get(route('payroll.ewt.getdata.former-employees'))->assertOk();
        // Initial Liability Estimation: the renewal legs (visa, insurance, permit, medical, quota) carry this year's costs.
        $legs = collect($this->get(route('people.liability.index'))->assertOk()->viewData('currentLegs'))->pluck('value', 'label');
        foreach (['Insurance', 'Work Permit', 'Medical', 'Quota Slot'] as $leg) {
            $this->assertGreaterThan(0, (float) $legs->first(fn ($v, $label) => str_starts_with($label, $leg)), "{$leg} on the liability page");
        }
    }

    public function test_settlements_open_and_count_the_leavers_days_and_used_leave()
    {
        $this->signIn('hr');
        $settled = DB::table('final_settlements as f')->join('employees as e', 'e.id', '=', 'f.employee_id')
            ->where('e.resort_id', $this->resortId)->get(['f.id', 'f.status', 'e.id as employee_id']);
        $this->assertEqualsCanonicalizing(['finalized', 'review'], $settled->pluck('status')->all());
        foreach ($settled as $s) {
            $this->get(route('final.settlement.review', $s->id))->assertOk();
        }
        // The settled leaver's last month: paid days found (attendance + roster day offs), leave already taken subtracted.
        $leaver = \App\Models\Employee::find($settled->firstWhere('status', 'finalized')->employee_id);
        $calc = (new \App\Services\FinalSettlementService)->calculateFinalMonthData($leaver, $this->resortId);
        $this->assertGreaterThan(0, $calc['worked_days']);
        $this->assertLessThanOrEqual($calc['expected_working_days'], $calc['worked_days']);
        // Approved annual leave in the leaver's last leave year (which ends on their last working day) comes off what is
        // paid out — the balance is max(0, accrued - used).
        $lastDay = DB::table('employee_resignation')->where('employee_id', $leaver->id)->where('status', 'Approved')->value('last_working_day');
        $annual = fn () => (float) collect((new \App\Services\FinalSettlementService)->calculateFinalMonthData($leaver->fresh(), $this->resortId)['leave_breakdown'])
            ->firstWhere('leave_type', 'Annual Leave')['available_days'];
        $before = $annual();
        if ($before > 0) {
            DB::table('employees_leaves')->insert(['resort_id' => $this->resortId, 'emp_id' => $leaver->id, 'total_days' => 1, 'status' => 'Approved',
                'leave_category_id' => DB::table('leave_categories')->where('resort_id', $this->resortId)->where('leave_type', 'Annual Leave')->value('id'),
                'from_date' => $lastDay, 'to_date' => $lastDay, 'created_at' => now(), 'updated_at' => now()]);
            $this->assertLessThan($before, $annual(), 'approved leave is subtracted from the balance');
        } else {
            DB::table('employees_leaves')->where('emp_id', $leaver->id)->where('status', 'Approved')->update(['status' => 'Rejected']);
            $this->assertGreaterThan($before, $annual(), 'approved leave is subtracted from the balance');
        }
    }
}
