<?php

namespace Tests\Feature;

use App\Helpers\Common;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/**
 * Budget approval notifications: one notice per action, each person once, never the
 * clicker — HR → Finance, Finance → GM, GM approved (per department and approve-all).
 */
class BudgetNotificationTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    private array $depts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();
        $this->depts = DB::table('resort_departments')->where('resort_id', $this->resortId)->pluck('id', 'name')->all();
        foreach ($this->depts as $deptId) {
            DB::table('manning_responses')->insert(['resort_id' => $this->resortId, 'dept_id' => $deptId, 'year' => 2027, 'status' => 'submitted',
                'employment_type' => 'Permanent', 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    /** employee id => how many notices titled $title they got. */
    private function received(string $title): array
    {
        return DB::table('resort_notifications')->where('resort_id', $this->resortId)->where('type', $title)
            ->selectRaw('user_id, count(*) n')->groupBy('user_id')->pluck('n', 'user_id')->map(fn ($n) => (int) $n)->all();
    }

    private function id(string $login): int
    {
        return $this->emp($login)->id;
    }

    /** HOD + XCOM of every department (the demo has HODs, plus EXCOM in Executive Office / HR / Finance). */
    private function leaders(array $deptNames): array
    {
        return DB::table('employees')->where('resort_id', $this->resortId)->whereIn('Dept_id', array_map(fn ($n) => $this->depts[$n], $deptNames))
            ->whereIn('rank', [1, 2])->pluck('id')->map(fn ($v) => (int) $v)->all();
    }

    private function assertRecipients(array $expected, string $title, string $why): void
    {
        $got = $this->received($title);
        sort($expected);
        $ids = array_keys($got);
        sort($ids);
        $this->assertSame(array_values(array_unique($expected)), $ids, $why);
        $this->assertSame([1], array_values(array_unique($got)), 'each person gets one notice per action');
    }

    public function test_hr_to_finance_then_finance_to_gm()
    {
        $this->as('hr')->postJson(route('resort.SendToFinance.manning.notification'), ['year' => 2027, 'employment_type' => 'Permanent'])
            ->assertJson(['success' => true]);
        $stage1 = array_merge($this->leaders(array_keys($this->depts)));
        $stage1 = array_values(array_diff($stage1, [$this->id('hr')]));
        $this->assertRecipients($stage1, 'Budget Review Update', 'Finance HOD+XCOM and every department\'s HOD+XCOM — not the HR clicker');
        $this->assertArrayNotHasKey($this->id('gm'), $this->received('Budget Review Update'), 'the GM is not involved yet');
        $clerk = DB::table('employees')->where('resort_id', $this->resortId)->where('Dept_id', $this->depts['Finance'])->where('rank', 6)->value('id');
        $this->assertArrayNotHasKey($clerk, $this->received('Budget Review Update'), 'not every Finance staff member');

        DB::table('resort_notifications')->where('resort_id', $this->resortId)->delete();
        $this->as('finance')->postJson(route('resort.SendToFinance.manning.notification'), ['year' => 2027, 'employment_type' => 'Permanent'])
            ->assertJson(['success' => true]);
        $stage2 = array_merge($this->leaders(array_keys($this->depts)), [$this->id('gm')]);
        $stage2 = array_values(array_diff($stage2, [$this->id('finance')]));
        $this->assertRecipients($stage2, 'Budget Review Update', 'the other Finance lead, GM, HR leads and department leads — not the Finance clicker');
        $this->assertArrayHasKey($this->id('finance.manager'), $this->received('Budget Review Update'));

        $note = DB::table('resort_notifications')->where('resort_id', $this->resortId)->where('user_id', $this->id('gm'))->first();
        $this->assertStringContainsString('forwarded to the GM', $note->message);
        $this->assertSame('budget', $note->page_id);
        $this->assertSame(route('resort.budget.viewbudget', ['year' => 2027]), Common::getNotificationUrl($note), 'opens that year\'s budget');
    }

    public function test_gm_approval_says_locked_and_reaches_hr_finance_and_the_department()
    {
        DB::table('manning_responses')->where('resort_id', $this->resortId)->update(['budget_process_status' => 'GM']);
        $kitchen = DB::table('manning_responses')->where('resort_id', $this->resortId)->where('dept_id', $this->depts['Kitchen'])->value('id');

        $this->as('gm')->postJson(route('resort.budget.approve'), ['budget_id' => $kitchen, 'department_id' => $this->depts['Kitchen'], 'year' => 2027])
            ->assertJson(['success' => true]);
        $expected = array_diff($this->leaders(['Human Resources', 'Finance', 'Kitchen']), [$this->id('gm')]);
        $this->assertRecipients(array_values($expected), 'Budget Approved', 'HR, Finance and Kitchen leads only');
        $this->assertStringContainsString('approved by the GM and locked',
            DB::table('resort_notifications')->where('resort_id', $this->resortId)->where('type', 'Budget Approved')->value('message'));

        DB::table('resort_notifications')->where('resort_id', $this->resortId)->delete();
        $this->as('gm')->postJson(route('resort.budget.approveAll'), ['year' => 2027])->assertJson(['success' => true]);
        $others = array_diff(array_keys($this->depts), ['Kitchen']); // Kitchen was already approved
        $expected = array_diff($this->leaders(array_merge(['Human Resources', 'Finance'], $others)), [$this->id('gm')]);
        $this->assertRecipients(array_values(array_unique($expected)), 'Budget Approved', 'approve-all: HR, Finance and every approved department, GM excluded');
    }
}
