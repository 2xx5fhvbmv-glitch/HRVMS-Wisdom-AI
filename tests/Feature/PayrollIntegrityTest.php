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
 * Payroll G1 (founder, 2026-11-06): every figure is computed on the server — a tampered POST saves the server's
 * value, HR inputs are kept with who/when, attendance edits are no longer replayed as overrides, and every
 * approval step re-checks the saved figures (runs already in the chain included).
 */
class PayrollIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    private int $resortId;
    private object $run;
    private object $line;   // one employee's saved review in the pending run

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'demo.enabled' => true, 'demo.password' => 'Demo#Pass2026', 'demo.resort_code' => 'DEMOTEST96',
            'demo.resort_email' => 'resort@demo.thewisdom.ai', 'demo.prefix' => 'DMI']);
        Demo::refresh();
        Storage::fake(config('settings.storage_driver'));
        (new \ReflectionClass(StorageHelper::class))->setStaticPropertyValue('cachedDisk', null);
        Mail::fake();
        Http::fake(['api.aladhan.com/*' => Http::response(['data' => ['hijri' => ['month' => ['number' => 3]]]])]);
        $this->artisan('demo:reset', ['--force' => true])->assertSuccessful();
        Demo::refresh();
        $this->resortId = Demo::resortId();
        $this->run = DB::table('payroll')->where('resort_id', $this->resortId)->where('payroll_category', 'Permanent')->where('status', 'pending_approval')->first();
        $this->line = DB::table('payroll_reviews')->where('payroll_id', $this->run->id)->where('net_salary', '>', 0)->orderBy('id')->first();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    private function signIn(string $login): void
    {
        auth('resort-admin')->logout();
        foreach (app('router')->getRoutes()->getRoutes() as $route) {
            $route->flushController();
        }
        $this->post(route('resort.login'), ['email' => "{$login}@demo.thewisdom.ai", 'password' => 'Demo#Pass2026'])->assertOk();
    }

    private function backToDraft(): void
    {
        DB::table('payroll')->where('id', $this->run->id)->update(['status' => 'draft']);
    }

    private function saved(string $table, string $field): float
    {
        return (float) DB::table($table)->where('payroll_id', $this->run->id)->where('employee_id', $this->line->employee_id)->value($field);
    }

    public function test_a_tampered_review_saves_the_server_figures()
    {
        $before = ['earned_salary' => (float) $this->line->earned_salary, 'net_salary' => (float) $this->line->net_salary, 'total_earnings' => (float) $this->line->total_earnings];
        $this->backToDraft();
        $this->signIn('finance');
        $this->postJson(route('payroll.saveReviews'), ['payroll_id' => $this->run->id, 'reviewData' => [[
            'id' => $this->line->Emp_id, 'earnedSalary' => 999999, 'earningsNormal' => 999999, 'overtimeTotal' => 5000, 'serviceCharge' => 5000,
            'totalDeductions' => 0, 'earningsAllowance' => 5000, 'allowances' => [['type' => 'Bonus', 'amount' => 5000, 'amount_unit' => 'USD']],
        ]]])->assertOk()->assertJson(['success' => true]);

        foreach ($before as $field => $value) {
            $this->assertEqualsWithDelta($value, $this->saved('payroll_reviews', $field), 0.01, "{$field} is the server's, not the posted one");
        }
        $this->assertSame(0, DB::table('payroll_review_allowances')->where('payroll_review_id', $this->line->id)->where('allowance_type', 'Bonus')->count());
    }

    public function test_a_tampered_deduction_saves_the_server_figures_and_keeps_hr_inputs_with_who_and_when()
    {
        $pension = $this->saved('payroll_deductions', 'pension');
        $ewt = $this->saved('payroll_deductions', 'ewt');
        $this->backToDraft();
        $this->signIn('finance');
        $tampered = ['id' => $this->line->Emp_id, 'attendanceDeduction' => 0, 'cityLedger' => 40, 'staffShop' => 0, 'advanceLoan' => 0,
            'pension' => 0, 'ewt' => 0, 'other' => 15, 'total' => 0];
        $this->postJson(route('payroll.saveDeductions'), ['payroll_id' => $this->run->id, 'DeductionData' => [$tampered]])
            ->assertStatus(422)->assertJson(['reason_required' => true]);   // a changed HR input needs a reason
        $this->postJson(route('payroll.saveDeductions'), ['payroll_id' => $this->run->id, 'reason' => 'October laundry and tuck shop', 'DeductionData' => [$tampered]])
            ->assertOk()->assertJson(['success' => true]);

        $this->assertEqualsWithDelta($pension, $this->saved('payroll_deductions', 'pension'), 0.01);
        $this->assertEqualsWithDelta($ewt, $this->saved('payroll_deductions', 'ewt'), 0.01);
        $this->assertEqualsWithDelta(40, $this->saved('payroll_deductions', 'city_ledger'), 0.01, 'city ledger is an HR input');
        $this->assertEqualsWithDelta(15, $this->saved('payroll_deductions', 'other'), 0.01, '"Other" is an HR input');
        $parts = DB::table('payroll_deductions')->where('payroll_id', $this->run->id)->where('employee_id', $this->line->employee_id)->first();
        $this->assertEqualsWithDelta($parts->attendance_deduction + $parts->city_ledger + $parts->staff_shop + $parts->advance_loan + $parts->pension + $parts->ewt + $parts->other,
            (float) $parts->total_deductions, 0.01, 'the total is the sum, worked out on the server');

        $entry = DB::table('payroll_manual_entries')->where('payroll_id', $this->run->id)->where('employee_id', $this->line->employee_id)->where('kind', 'city_ledger')->whereNull('deleted_at')->first();
        $this->assertNotNull($entry->created_by);
        $this->assertNotEmpty($entry->reason);

        // Changing an input keeps the old entry, marked removed.
        $this->postJson(route('payroll.saveDeductions'), ['payroll_id' => $this->run->id, 'reason' => 'corrected', 'DeductionData' => [['id' => $this->line->Emp_id, 'cityLedger' => 25, 'other' => 15]]])->assertOk();
        $this->assertSame(1, DB::table('payroll_manual_entries')->where('id', $entry->id)->whereNotNull('deleted_at')->whereNotNull('deleted_by')->count());
        $this->assertEqualsWithDelta(25, $this->saved('payroll_deductions', 'city_ledger'), 0.01);
    }

    public function test_hr_entries_need_a_reason_and_are_kept_when_removed()
    {
        $this->backToDraft();
        $this->signIn('finance');
        $add = fn (array $extra) => $this->postJson(route('payroll.entries.add'), ['payroll_id' => $this->run->id, 'employee' => $this->line->Emp_id,
            'currency' => 'USD'] + $extra);

        $add(['kind' => 'extra_payment', 'label' => 'Bonus', 'amount' => 200])->assertStatus(422);   // no reason
        $add(['kind' => 'extra_payment', 'label' => 'Bonus', 'amount' => 200, 'reason' => 'Covered two night shifts'])
            ->assertOk()->assertJsonPath("rows.{$this->line->Emp_id}.review.total_earnings", round((float) $this->line->total_earnings + 200, 2));
        $this->assertSame(1, DB::table('payroll_review_allowances')->where('payroll_review_id', $this->line->id)->where('allowance_type', 'Bonus')->where('amount', 200)->count());
        $add(['kind' => 'other_deduction', 'label' => 'Uniform', 'amount' => 30, 'reason' => 'Lost uniform'])->assertOk();
        $this->assertEqualsWithDelta(30, $this->saved('payroll_deductions', 'other'), 0.01);

        $entry = DB::table('payroll_manual_entries')->where('payroll_id', $this->run->id)->where('kind', 'extra_payment')->first();
        $this->assertSame('Covered two night shifts', $entry->reason);
        $this->assertNotNull($entry->created_by);
        $this->postJson(route('payroll.entries.list'), ['payroll_id' => $this->run->id])->assertOk()->assertJsonCount(2, 'entries');

        $this->postJson(route('payroll.entries.remove'), ['payroll_id' => $this->run->id, 'entry_id' => $entry->id])->assertOk();
        $this->assertNotNull(DB::table('payroll_manual_entries')->where('id', $entry->id)->value('deleted_by'), 'kept, marked removed');
        $this->assertEqualsWithDelta((float) $this->line->total_earnings, $this->saved('payroll_reviews', 'total_earnings'), 0.01);
    }

    public function test_an_extra_payment_label_is_plain_text()   // security audit run-3 M10
    {
        $this->backToDraft();
        $this->signIn('finance');
        $this->postJson(route('payroll.entries.add'), ['payroll_id' => $this->run->id, 'employee' => $this->line->Emp_id, 'currency' => 'USD', 'amount' => 10,
            'kind' => 'extra_payment', 'label' => '<img src=x onerror=alert(1)>Bonus', 'reason' => 'night shifts'])->assertOk();
        $this->assertSame('Bonus', DB::table('payroll_manual_entries')->where('payroll_id', $this->run->id)->where('kind', 'extra_payment')->value('label'));
    }

    public function test_the_city_ledger_file_is_read_on_the_server()
    {
        $this->backToDraft();
        $this->signIn('finance');
        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $book->getActiveSheet()->fromArray([['Employee ID', 'Name', 'Amount (USD)'], [$this->line->Emp_id, 'x', '1,234.50'], ['NOT-IN-RUN', 'y', 10]]);
        $path = tempnam(sys_get_temp_dir(), 'cl') . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);
        $file = new \Illuminate\Http\UploadedFile($path, 'city-ledger.xlsx', null, null, true);

        $this->post(route('payroll.cityLedger.upload'), ['payroll_id' => $this->run->id, 'file' => $file], ['Accept' => 'application/json'])->assertStatus(422);   // reason
        $this->post(route('payroll.cityLedger.upload'), ['payroll_id' => $this->run->id, 'file' => $file, 'reason' => 'October ledger'], ['Accept' => 'application/json'])
            ->assertOk()->assertJson(['not_found' => ['NOT-IN-RUN']]);
        $usd = DB::table('resort_site_settings')->where('resort_id', $this->resortId)->value('currency') === 'MVR'
            ? 1234.5 * (float) DB::table('resort_site_settings')->where('resort_id', $this->resortId)->value('DollertoMVR') : 1234.5;
        $this->assertEqualsWithDelta($usd, $this->saved('payroll_deductions', 'city_ledger'), 0.01, '"1,234.50" is not cut to 1');
    }

    public function test_attendance_edits_are_not_replayed_as_overrides()
    {
        $present = $this->saved('payroll_time_and_attandance', 'present_days');
        DB::table('payroll_attendance_activity_log')->insert(['resort_id' => $this->resortId, 'payroll_id' => $this->run->id, 'employee_id' => $this->line->employee_id,
            'user_id' => $this->line->employee_id, 'field' => 'present_days', 'old_value' => $present, 'new_value' => 99, 'created_at' => now(), 'updated_at' => now()]);
        $this->backToDraft();
        $this->signIn('finance');
        $this->postJson(route('payroll.saveAttendance'), ['payroll_id' => $this->run->id, 'attendance' => [['id' => $this->line->Emp_id, 'present' => 99, 'absent' => 0, 'regularOT' => 80]]])->assertOk();

        $this->assertEqualsWithDelta($present, $this->saved('payroll_time_and_attandance', 'present_days'), 0.01);
        $this->assertEqualsWithDelta((float) $this->line->earned_salary, $this->saved('payroll_reviews', 'earned_salary'), 0.01);
    }

    public function test_a_run_already_in_the_chain_with_altered_figures_cannot_be_approved()
    {
        // As a browser-tampered save before this fix would have left it.
        DB::table('payroll_reviews')->where('id', $this->line->id)->update(['net_salary' => $this->line->net_salary + 1000, 'total_earnings' => $this->line->total_earnings + 1000]);
        $this->signIn('finance');
        $this->postJson(route('payroll.approve'), ['payroll_id' => $this->run->id, 'action' => 'approve'])->assertStatus(422)
            ->assertJsonFragment(['success' => false])->assertSee($this->line->Emp_id);
        $this->assertSame(0, DB::table('payroll_approvals')->where('payroll_id', $this->run->id)->where('status', 'approved')->count());

        // Reject is always allowed: back to draft, where saving recomputes the figures.
        $this->postJson(route('payroll.approve'), ['payroll_id' => $this->run->id, 'action' => 'reject', 'remarks' => 'figures changed'])->assertOk();
        $this->assertSame('draft', DB::table('payroll')->where('id', $this->run->id)->value('status'));
    }

    public function test_a_casual_run_is_computed_on_the_server_too()
    {
        $run = DB::table('payroll')->where('resort_id', $this->resortId)->where('payroll_category', 'Casual')->where('status', 'pending_approval')->first();
        $line = DB::table('payroll_deductions')->where('payroll_id', $run->id)->orderBy('id')->first();
        $review = DB::table('payroll_reviews')->where('payroll_id', $run->id)->where('employee_id', $line->employee_id)->first();
        DB::table('payroll')->where('id', $run->id)->update(['status' => 'draft']);
        $this->signIn('finance');

        // Pension / EWT / advance were typed by HR on the Casual screen — now the posted ones are ignored.
        $this->postJson(route('payroll.saveDeductions'), ['payroll_id' => $run->id, 'DeductionData' => [[
            'id' => $line->Emp_id, 'attendanceDeduction' => 300, 'cityLedger' => 0, 'staffShop' => 0, 'advanceLoan' => 400,
            'pension' => 500, 'ewt' => 600, 'other' => 0, 'total' => 1800,
        ]]])->assertOk()->assertJson(['success' => true]);
        $saved = DB::table('payroll_deductions')->where('id', $line->id)->first();
        foreach (['attendance_deduction', 'advance_loan', 'pension', 'ewt', 'total_deductions'] as $field) {
            $this->assertEqualsWithDelta((float) $line->$field, (float) $saved->$field, 0.01, $field);
        }

        $this->postJson(route('payroll.saveReviews'), ['payroll_id' => $run->id, 'reviewData' => [[
            'id' => $line->Emp_id, 'earnedSalary' => 99999, 'earningsNormal' => 99999, 'totalDeductions' => 0, 'serviceProviderCommission' => 0,
        ]]])->assertOk();
        $this->assertEqualsWithDelta((float) $review->net_salary, (float) DB::table('payroll_reviews')->where('id', $review->id)->value('net_salary'), 0.01);
        $this->assertEqualsWithDelta((float) $review->service_provider_commission, (float) DB::table('payroll_reviews')->where('id', $review->id)->value('service_provider_commission'), 0.01);

        $this->postJson(route('payroll.send.approval'), ['payroll_id' => $run->id])->assertOk();
        foreach (['finance', 'hr', 'gm'] as $login) {
            $this->signIn($login);
            $this->postJson(route('payroll.approve'), ['payroll_id' => $run->id, 'action' => 'approve'])->assertOk();
        }
        $this->assertSame('locked', DB::table('payroll')->where('id', $run->id)->value('status'));
    }

    public function test_the_run_pages_carry_the_reason_fields_and_extra_payment_window()
    {
        $this->signIn('finance');
        $this->get(route('payroll.run'))->assertOk()
            ->assertSee('id="extraPayment-modal"', false)->assertSee('id="city-ledger-reason"', false)
            ->assertSee('id="total-ser-reason"', false)->assertSee('id="deduction_reason"', false);
        $this->get(route('resort.casualPayroll.index'))->assertOk()->assertSee('id="cp-ded-reason"', false);
    }

    public function test_draft_to_lock_ends_on_the_server_total()
    {
        DB::table('payroll_reviews')->where('id', $this->line->id)->update(['net_salary' => 1]);   // stale/tampered draft
        $this->backToDraft();
        $this->signIn('finance');
        $this->postJson(route('payroll.send.approval'), ['payroll_id' => $this->run->id])->assertOk();
        $this->assertEqualsWithDelta((float) $this->line->net_salary, $this->saved('payroll_reviews', 'net_salary'), 0.01, 'sending recomputes');

        foreach (['finance', 'hr', 'gm'] as $login) {
            $this->signIn($login);
            $this->postJson(route('payroll.approve'), ['payroll_id' => $this->run->id, 'action' => 'approve'])->assertOk();
        }
        $run = DB::table('payroll')->find($this->run->id);
        $this->assertSame('locked', $run->status);
        $this->assertEqualsWithDelta((float) DB::table('payroll_reviews')->where('payroll_id', $run->id)->sum('net_salary'), (float) $run->total_payroll, 0.01);

        $this->postJson(route('payroll.approve'), ['payroll_id' => $this->run->id, 'action' => 'approve'])->assertStatus(400);
    }
}
