<?php

namespace Tests\Feature;

use App\Helpers\Common;
use App\Models\ResortAdmin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/** Security audit wave 1, resort portal writes that had no permission check: W1-04/05/07/16/17. */
class ResortPortalAccessTest extends TestCase
{
    use DatabaseTransactions;

    private const GATES = [
        ['resort.sitesettings', 'edit'], ['resort.emailconfig.index', 'edit'], ['resort.masterimport.index', 'create'], ['resort.benifitgrid.index', 'create'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Queue::fake();
    }

    /** An active staff login whose position holds none of the ticks above. */
    private function staffWithoutTicks(): ResortAdmin
    {
        foreach (ResortAdmin::where('type', '!=', 'super')->where('status', 'Active')->whereHas('GetEmployee', fn ($q) => $q->where('status', 'Active'))->limit(200)->get() as $user) {
            $this->actingAs($user, 'resort-admin');
            if (!collect(self::GATES)->contains(fn ($g) => Common::checkRouteWisePermission($g[0], config('settings.resort_permissions.' . $g[1])))) {
                return $user;
            }
        }
        $this->markTestSkipped('No staff user without these ticks in this database.');
    }

    public function test_unticked_staff_cannot_write_settings_permissions_import_or_grid()
    {
        $me = $this->staffWithoutTicks();
        $other = DB::table('resorts')->where('id', '!=', $me->resort_id)->value('id');
        $before = DB::table('resort_site_settings')->where('resort_id', $other)->value('currency');

        $this->post(route('resort.updatesitesettings'), ['resort_id' => $other, 'currency' => 'XXX'])->assertForbidden();
        $this->post(route('resort.emailconfig.store'), ['host' => 'evil.test'])->assertForbidden();
        $this->post(route('resorts.update_internal_permissions'), ['department' => 1, 'position' => 1])->assertForbidden();
        $this->post(route('resort.Depat_Position_Emp_Import'), ['Employeefile' => UploadedFile::fake()->create('e.xlsx', 1)])->assertForbidden();
        $this->post(route('resort.benifitgrid.store'), [])->assertForbidden();

        $this->assertSame($before, DB::table('resort_site_settings')->where('resort_id', $other)->value('currency'));
        Queue::assertNothingPushed();
    }

    public function test_currency_switch_only_touches_the_callers_resort()
    {
        $me = $this->staffWithoutTicks();
        $other = DB::table('resort_site_settings')->where('resort_id', '!=', $me->resort_id)->first();
        $this->post(route('sitesetting.UpdateCurrency'), ['resortid' => $other->resort_id, 'currency' => $other->currency === 'MVR' ? 'USD' : 'MVR']);
        $this->assertSame($other->currency, DB::table('resort_site_settings')->where('resort_id', $other->resort_id)->value('currency'));
    }

    public function test_notifications_go_to_a_private_channel_per_resort()   // NV01
    {
        $this->assertSame(['private-resort-notifications.5', 'private-resort-notifications.7'],
            array_map(fn ($c) => $c->name, \App\Events\ResortNotificationEvent::channelsFor('5, 7,5')));

        $me = $this->staffWithoutTicks();
        $other = DB::table('resorts')->where('id', '!=', $me->resort_id)->value('id');
        $auth = fn ($rid) => $this->post('/broadcasting/auth', ['socket_id' => '1234.5678', 'channel_name' => 'private-resort-notifications.' . $rid]);
        $auth($me->resort_id)->assertOk();
        $auth($other)->assertForbidden();
    }

    public function test_master_admin_still_passes_the_gates()
    {
        $master = ResortAdmin::where('type', 'super')->where('status', 'Active')->firstOrFail();
        $this->actingAs($master, 'resort-admin');
        foreach (self::GATES as [$page, $tick]) {
            $this->assertTrue((bool) Common::checkRouteWisePermission($page, config('settings.resort_permissions.' . $tick)), $page);
        }
    }
}
