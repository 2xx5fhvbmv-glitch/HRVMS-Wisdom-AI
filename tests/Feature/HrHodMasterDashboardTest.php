<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** The HR department's HOD gets the HR Master Dashboard; other modules and other HODs are unchanged. */
class HrHodMasterDashboardTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();
    }

    private function landing(string $login): string
    {
        $this->app['auth']->forgetGuards();
        return $this->withoutMiddleware(VerifyCsrfToken::class)
            ->post(route('resort.login'), ['email' => "{$login}@demo.thewisdom.ai", 'password' => 'Demo#Pass2026'])->json('redirect_url');
    }

    public function test_hr_department_hod_lands_on_and_can_open_the_hr_master_dashboard()
    {
        $this->assertSame('2', (string) $this->emp('hr.manager')->rank, 'the HR Manager is the HR department HOD');
        $this->assertStringEndsWith('/master/hr-dashboard', $this->landing('hr.manager'));

        $this->as('hr.manager')->get(route('resort.master.hr_dashboard'))->assertOk();
        $this->as('hr.manager')->get(route('resort.master.hod_dashboard'))->assertRedirect(route('resort.master.hr_dashboard'));
        // Only the master dashboard is widened — module dashboards stay HOD-level.
        $this->as('hr.manager')->get(route('leave.dashboard'))->assertRedirect(route('leave.hoddashboard'));
    }

    public function test_other_hods_keep_the_hod_master_dashboard()
    {
        $this->assertStringEndsWith('/master/hod-dashboard', $this->landing('hod.housekeeping'));
        $this->as('hod.housekeeping')->get(route('resort.master.hr_dashboard'))->assertRedirect(route('resort.master.hod_dashboard'));
        $this->as('hod.housekeeping')->get(route('resort.master.hod_dashboard'))->assertOk();
    }

    public function test_header_logo_points_hr_hod_at_the_hr_dashboard()
    {
        $page = $this->as('hr.manager')->get(route('resort.master.hr_dashboard'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#href="' . preg_quote(route('resort.master.hr_dashboard'), '#') . '" class="brand-logo"#', $page);
    }

    public function test_without_position_access_the_hr_hod_still_gets_the_hr_dashboard()
    {
        \Illuminate\Support\Facades\DB::table('resorts')->where('id', $this->resortId)->update(['Position_access' => null]);
        $this->assertStringEndsWith('/master/hr-dashboard', $this->landing('hr.manager'));
        $this->as('hr.manager')->get(route('resort.master.hod_dashboard'))->assertRedirect(route('resort.master.hr_dashboard'));
        $this->as('hod.housekeeping')->get(route('resort.master.hod_dashboard'))->assertOk();
    }

    public function test_an_hr_department_named_hr_is_still_recognised()
    {
        \Illuminate\Support\Facades\DB::table('resort_departments')->where('resort_id', $this->resortId)->where('name', 'Human Resources')->update(['name' => 'HR']);
        // HR-dept EXCOM: HR-level module dashboards (was: treated as a non-HR EXCOM → EXCOM dashboard).
        $this->as('hr')->get(route('leave.dashboard'))->assertOk();
        $this->as('hr.manager')->get(route('resort.master.hod_dashboard'))->assertRedirect(route('resort.master.hr_dashboard'));
    }
}
