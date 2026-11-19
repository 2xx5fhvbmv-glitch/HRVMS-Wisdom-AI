<?php

namespace Tests\Feature;

use App\Http\Controllers\Resorts\WisdomChatController;
use App\Services\Wisdom\WisdomTools;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** WAI photo cards: the people a reply names (from that turn's lookups), only from the asker's resort and scope. */
class WisdomPeopleCardsTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        DB::connection('mysql_readonly')->setPdo(DB::connection()->getPdo());
        config(['services.openrouter.key' => 'test-key', 'services.openrouter.base_url' => 'https://openrouter.test/api/v1']);
        Http::swap(new \Illuminate\Http\Client\Factory);   // drop the demo setup's catch-all fake so ours answer
    }

    private function toolCall(string $name, array $args): array
    {
        return ['choices' => [['finish_reason' => 'tool_calls', 'message' => ['role' => 'assistant', 'content' => '',
            'tool_calls' => [['id' => 'c1', 'type' => 'function', 'function' => ['name' => $name, 'arguments' => json_encode($args)]]]]]]];
    }

    private function reply(string $text): array
    {
        return ['choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => $text]]]];
    }

    private function nameOf($emp): string
    {
        return trim($emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name);
    }

    public function test_the_people_a_reply_names_come_back_as_cards()
    {
        $hod = $this->emp('hod.kitchen');
        Http::fake(['openrouter.test/*' => Http::sequence()
            ->push($this->toolCall('find_employee', ['name' => $hod->Emp_id]))
            ->push($this->reply("{$this->nameOf($hod)} is the head of the kitchen.")), '*' => Http::response([])]);

        $res = $this->as('hr')->postJson(route('resort.wisdom.chat'), ['message' => 'Who runs the kitchen?'])->assertOk();

        $this->assertSame([[
            'id' => $hod->id, 'emp_id' => $hod->Emp_id, 'name' => $this->nameOf($hod),
        ]], array_map(fn ($p) => array_intersect_key($p, array_flip(['id', 'emp_id', 'name'])), $res->json('people')));
        $this->assertArrayNotHasKey('people_pick', $res->json());
        $this->assertNotEmpty($res->json('people.0.dept'));
        // and the stored conversation (shown again by the history call) carries no lookups
        $this->getJson(route('resort.wisdom.history'))->assertJsonMissingPath('messages.1.lookups');
    }

    public function test_an_ambiguous_name_asks_which_one()
    {
        $a = $this->emp('hod.kitchen');
        $b = $this->emp('manager');
        DB::table('resort_admins')->whereIn('id', [$a->Admin_Parent_id, $b->Admin_Parent_id])->update(['first_name' => 'Zayan']);
        $a->refresh(); $b->refresh();
        Http::fake(['openrouter.test/*' => Http::sequence()
            ->push($this->toolCall('find_employee', ['name' => 'Zayan']))
            ->push($this->reply("I found two people called Zayan: {$this->nameOf($a)} and {$this->nameOf($b)}. Which one do you mean?")), '*' => Http::response([])]);

        $res = $this->as('hr')->postJson(route('resort.wisdom.chat'), ['message' => 'Who is Zayan?'])->assertOk();

        $this->assertTrue($res->json('people_pick'));
        $this->assertEqualsCanonicalizing([$a->Emp_id, $b->Emp_id], array_column($res->json('people'), 'emp_id'));
        // tapping one re-asks with the emp_id, which resolves to exactly that person
        $one = WisdomTools::execute('find_employee', ['name' => $a->Emp_id], ['tier' => 'full', 'resort_id' => $this->resortId, 'can_db' => true, 'can_payroll' => true]);
        $this->assertSame(1, $one['matches']);
    }

    public function test_people_outside_the_resort_or_scope_never_appear()
    {
        $cards = new \ReflectionMethod(WisdomChatController::class, 'peopleCards');
        $other = DB::table('employees as e')->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->where('e.resort_id', '!=', $this->resortId)->whereNotNull('e.Emp_id')
            ->first(['e.Emp_id', DB::raw("TRIM(CONCAT(ra.first_name,' ',ra.last_name)) as name")]);
        [$people] = $cards->invoke(null, ['resort_id' => $this->resortId], [$other->Emp_id], "{$other->name} is mentioned here.", false);
        $this->assertSame([], $people, 'another resort');

        $hod = $this->emp('hod.kitchen');
        $hr = $this->emp('hr');
        [$people] = $cards->invoke(null, ['resort_id' => $this->resortId, 'dept_ids' => [(int) $hod->Dept_id]], [$hod->Emp_id, $hr->Emp_id],
            "{$this->nameOf($hod)} and {$this->nameOf($hr)}", false);
        $this->assertSame([$hod->Emp_id], array_column($people, 'emp_id'), 'department scope');
    }
}
