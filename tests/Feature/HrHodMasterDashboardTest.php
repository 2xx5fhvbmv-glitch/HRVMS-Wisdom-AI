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
}
