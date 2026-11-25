<?php

namespace Tests\Feature;

use App\Models\ResortAdmin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\Concerns\BuildsDemoOrganisation;
use Tests\TestCase;

/** Leaks found while checking the mobile QA report of 25 Nov 2026. */
class MobileQa2LeaksTest extends TestCase
{
    use DatabaseTransactions, BuildsDemoOrganisation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildDemoOrganisation();
    }

    private function app_as(string $login): ResortAdmin
    {
        $this->app['auth']->forgetGuards();
        foreach ($this->app['router']->getRoutes()->getRoutes() as $route) {
            $route->flushController();
        }
        $admin = ResortAdmin::where('email', "{$login}@demo.thewisdom.ai")->firstOrFail();
        Passport::actingAs($admin, [], 'api');
        return $admin;
    }

    private function msg(int $from, int $to, string $text): void
    {
        DB::table('conversation')->insert(['resort_id' => $this->resortId, 'type' => 'individual', 'type_id' => $to, 'sender_id' => $from,
            'message' => $text, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_chat_list_shows_only_my_own_conversations_and_their_last_message()
    {
        [$me, $friend, $a, $b] = [$this->emp('employee'), $this->emp('supervisor'), $this->emp('hr'), $this->emp('gm')];
        $this->msg($me->Admin_Parent_id, $friend->Admin_Parent_id, 'hello friend');
        $this->msg($friend->Admin_Parent_id, $a->Admin_Parent_id, 'PRIVATE between friend and hr');   // newer, not mine
        $this->msg($a->Admin_Parent_id, $b->Admin_Parent_id, 'PRIVATE between hr and gm');

        $this->app_as('employee');
        $body = $this->getJson('api/chat/list')->assertOk()->getContent();
        $this->assertStringNotContainsString('PRIVATE', $body);
        $people = collect($this->getJson('api/chat/list')->json('data') ?? $this->getJson('api/chat/list')->json('chats') ?? [])
            ->where('type', 'individual')->pluck('id')->all();
        $this->assertNotContains($a->Admin_Parent_id, $people);
        $this->assertNotContains($b->Admin_Parent_id, $people);
        $this->assertStringContainsString('hello friend', $body);
    }

    public function test_monthly_check_in_history_stays_in_my_resort_and_my_scope()
    {
        $other = DB::table('resorts')->where('id', '!=', $this->resortId)->value('id');
        $row = fn ($resort, $emp, $tag) => DB::table('monthly_checking_models')->insert(['Checkin_id' => $tag, 'resort_id' => $resort, 'emp_id' => $emp,
            'start_time' => '10:00', 'end_time' => '11:00', 'Meeting_Place' => 'Office', 'status' => 'Conducted', 'date_discussion' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now()]);
        $row($other, 999999, 'FOREIGN-RESORT');
        $row($this->resortId, $this->emp('hod.kitchen')->id, 'SOMEONE-ELSE');
        $row($this->resortId, $this->emp('employee')->id, 'MINE');

        $this->app_as('employee');
        $body = $this->getJson('api/monthlycheckin/monthly-checkin-history')->assertOk()->getContent();
        $this->assertStringNotContainsString('FOREIGN-RESORT', $body);
        $this->assertStringNotContainsString('SOMEONE-ELSE', $body);
    }

    public function test_congratulating_is_resort_scoped_and_once_per_person()
    {
        $other = DB::table('resorts')->where('id', '!=', $this->resortId)->value('id');
        $cat = DB::table('announcement_category')->value('id');
        $this->assertNotNull($cat, 'needs an announcement category');
        $ann = fn ($resort) => DB::table('announcement')->insertGetId(['resort_id' => $resort, 'title' => $cat, 'employee_id' => $this->emp('hr')->id,
            'message' => 'Well done', 'status' => 'Published', 'created_by' => 1, 'modified_by' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $foreign = $ann($other);
        $mine = $ann($this->resortId);

        $this->app_as('employee');
        $this->getJson('api/announcement/send-congratulation/' . base64_encode((string) $foreign))->assertJson(['success' => false]);
        $this->getJson('api/announcement/send-congratulation/' . base64_encode((string) $mine))->assertJson(['success' => true]);
        $this->getJson('api/announcement/send-congratulation/' . base64_encode((string) $mine))->assertJson(['success' => true]);
        $this->assertSame(1, DB::table('announcement_notification')->where('announcement_id', $mine)->count());
        $this->assertSame(0, DB::table('announcement_notification')->where('announcement_id', $foreign)->count());
    }
}
