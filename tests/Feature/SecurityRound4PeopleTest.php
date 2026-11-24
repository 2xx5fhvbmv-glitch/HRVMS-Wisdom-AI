<?php

namespace Tests\Feature;

use App\Http\Middleware\RevalidateBackHistory;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** Security audit run-4, People: probation letters, employee letters/logs, salary/letter exposure, appeals, report, occupancy, middleware. */
class SecurityRound4PeopleTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        Mail::fake();
    }

    public function test_n1_probation_letter_is_hr_only_checks_type_and_refuses_leavers()
    {
        $line = $this->emp('employee');
        $send = fn (array $data) => $this->postJson(route('probation.send-letter'), ['employee_id' => $line->id] + $data);

        $this->as('manager');
        $send(['type' => 'success'])->assertForbidden();
        $this->postJson(route('probation.letter-template'), ['type' => 'success', 'subject' => 'x', 'content' => 'x'])->assertForbidden();
        $this->assertSame($line->status, $line->fresh()->status);

        $this->as('hr');
        $send(['type' => 'offer'])->assertStatus(422);
        foreach (['Terminated', 'Inactive', 'Offboarding'] as $status) {
            $line->forceFill(['status' => $status])->save();
            $send(['type' => 'success'])->assertStatus(422);
            $send(['type' => 'failed'])->assertStatus(422);
            $this->assertSame($status, $line->fresh()->status, 'a leaver is never re-activated or re-failed by a letter');
        }
    }

    public function test_n6_employment_letter_and_logs_are_hr_only_and_sos_ids_are_resort_scoped()
    {
        $line = $this->emp('employee');

        $this->as('manager');
        $this->postJson(route('people.employees.sendEmploymentVerificationLetter', $line->id))->assertForbidden();
        $this->getJson(route('people.employees.employment-logs', ['employee_id' => $line->id]))->assertForbidden();

        $this->as('hr');
        $this->getJson(route('people.employees.employment-logs', ['employee_id' => $line->id]))->assertOk()->assertJson(['success' => true]);

        $otherResort = DB::table('resorts')->where('id', '!=', $this->resortId)->value('id');
        $foreignTeam = DB::table('sos_teams')->insertGetId(['resort_id' => $otherResort, 'name' => 'Foreign', 'created_at' => now(), 'updated_at' => now()]);
        $foreignRole = DB::table('sos_role_management')->insertGetId(['resort_id' => $otherResort, 'name' => 'Foreign', 'created_at' => now(), 'updated_at' => now()]);
        $team = DB::table('sos_teams')->insertGetId(['resort_id' => $this->resortId, 'name' => 'Ours', 'created_at' => now(), 'updated_at' => now()]);
        $role = DB::table('sos_role_management')->insertGetId(['resort_id' => $this->resortId, 'name' => 'Ours', 'created_at' => now(), 'updated_at' => now()]);

        $this->postJson(route('people.employees.assign-team'), ['emp_id' => $line->id, 'team_id' => $foreignTeam, 'role_id' => $foreignRole])
            ->assertStatus(422)->assertJsonValidationErrors(['team_id', 'role_id']);
        $this->postJson(route('people.employees.assign-team'), ['emp_id' => $line->id, 'team_id' => $team, 'role_id' => $role])
            ->assertOk()->assertJson(['status' => 'success']);
    }

    public function test_n7_salary_increment_and_letter_downloads_are_hr_only()
    {
        $this->as('manager');
        $this->getJson(route('people.salary-increment.view', 999999999))->assertForbidden();
        $this->postJson(route('people.salary-increment.summary-store'), [])->assertForbidden();
        $this->get(route('people.salary-increment.summary-view'))->assertForbidden();
        $this->get(route('people.salary-increment.download-letter', 999999999))->assertForbidden();
        $this->get(route('promotion.download-letter', 999999999))->assertForbidden();
        $this->get(route('people.transfer.download-letter', 999999999))->assertForbidden();
        $this->get(route('people.transfer.preview-letter', 999999999))->assertForbidden();
        $this->postJson(route('people.transfer.send-letter', 999999999))->assertForbidden();

        $this->as('hr');   // the gate lets HR through to the (missing) record
        $this->getJson(route('people.salary-increment.view', 999999999))->assertNotFound();
        $this->get(route('people.salary-increment.download-letter', 999999999))->assertNotFound();
        $this->get(route('promotion.download-letter', 999999999))->assertNotFound();
        $this->get(route('people.transfer.download-letter', 999999999))->assertNotFound();
    }

    public function test_appeal_submitter_can_withdraw_but_only_hr_decides()
    {
        $line = $this->emp('employee');
        $grievance = DB::table('grivance_submission_models')->insertGetId([
            'resort_id' => $this->resortId, 'Grivance_id' => 'GRV-R4-APPEAL', 'Committee_id' => 0, 'Grivance_Cat_id' => 0,
            'Grivance_Sub_cat' => 0, 'Employee_id' => $line->id, 'grievance_informally' => 'No', 'Gm_Resoan' => '',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $appeal = DB::table('grievance_appeals')->insertGetId([
            'resort_id' => $this->resortId, 'grievance_id' => $grievance, 'appeal_no' => 'APL-R4', 'submitted_by' => $line->id,
            'status' => 'Pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $id = base64_encode((string) $appeal);

        $this->as('supervisor');   // not the submitter, not HR
        $this->postJson(route('GrievanceAndDisciplinery.Appeals.Withdraw', $id))->assertForbidden();

        $this->as('employee');
        $this->postJson(route('GrievanceAndDisciplinery.Appeals.Decide', $id), ['decision' => 'Upheld', 'final_status' => 'Resolved'])->assertForbidden();
        $this->postJson(route('GrievanceAndDisciplinery.Appeals.Withdraw', $id))->assertOk()->assertJson(['success' => true]);
        $this->assertSame('Withdrawn', DB::table('grievance_appeals')->where('id', $appeal)->value('status'));
    }

    public function test_grievance_report_skips_soft_deleted_cases()
    {
        $line = $this->emp('employee');
        $row = fn (string $code, $deleted) => DB::table('grivance_submission_models')->insert([
            'resort_id' => $this->resortId, 'Grivance_id' => $code, 'Committee_id' => 0, 'Grivance_Cat_id' => 0,
            'Grivance_Sub_cat' => 0, 'Employee_id' => $line->id, 'grievance_informally' => 'No', 'Gm_Resoan' => '',
            'created_at' => now(), 'updated_at' => now(), 'deleted_at' => $deleted,
        ]);
        $row('GRV-R4-LIVE', null);
        $row('GRV-R4-GONE', now());

        $this->as('hr');
        $html = $this->postJson(route('resort.report.grievance.run'), ['report' => 'grievance_register'])->assertOk()->json('html');
        $this->assertStringContainsString('GRV-R4-LIVE', $html);
        $this->assertStringNotContainsString('GRV-R4-GONE', $html);
    }

    public function test_occupancy_store_returns_the_saved_row_not_the_tables_first_row()
    {
        $this->as('hr');
        $this->postJson(route('resort.occupancy.store'), [
            'occupancydate' => '2031-03-15', 'occupancyinPer' => 37, 'occupancytotalRooms' => 120, 'occupancyOccupiedRooms' => 44,
        ])->assertOk()->assertJson(['success' => true, 'data' => [76, 37]]);
    }

    public function test_revalidate_middleware_runs_a_download_once()
    {
        $calls = 0;
        $response = (new RevalidateBackHistory)->handle(Request::create('/'), function () use (&$calls) {
            $calls++;
            return new StreamedResponse(fn () => null);
        });
        $this->assertSame(1, $calls);
        $this->assertInstanceOf(StreamedResponse::class, $response);
    }
}
