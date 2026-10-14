<?php

namespace Tests\Concerns;

use App\Helpers\StorageHelper;
use App\Models\Employee;
use App\Models\ResortAdmin;
use App\Support\Demo\Demo;
use Database\Seeders\Demo\DemoEnvSeeder;
use Database\Seeders\Demo\DemoOrganisationSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * A throwaway Demo ENV resort with its organisation and logins (HR Director, HR Manager,
 * Finance Director, GM, HODs…), without the time/payroll slices. Use inside DatabaseTransactions.
 */
trait BuildsDemoOrganisation
{
    protected int $resortId;

    protected function buildDemoOrganisation(): void
    {
        config(['cache.default' => 'array', 'demo.enabled' => true, 'demo.password' => 'Demo#Pass2026', 'demo.resort_code' => 'DEMOTEST98', 'demo.prefix' => 'DMX']);
        Demo::refresh();
        Storage::fake(config('settings.storage_driver'));
        (new \ReflectionClass(StorageHelper::class))->setStaticPropertyValue('cachedDisk', null);
        Http::fake(['*' => Http::response(['ok' => true])]);
        Event::fake([\App\Events\ResortNotificationEvent::class]);

        $seeder = new DemoEnvSeeder;
        $call = fn ($m, ...$a) => (new \ReflectionMethod($seeder, $m))->invoke($seeder, ...$a);
        $this->resortId = $call('resort');
        Demo::refresh();
        $admin = $call('masterAdmin', $this->resortId);
        Auth::guard('resort-admin')->setUser($admin);
        $call('fullMenu', $this->resortId);
        mt_srand(11);
        (new DemoOrganisationSeeder($this->resortId, $admin->id))->seed();
        Auth::guard('resort-admin')->logout();
    }

    protected function emp(string $login): Employee
    {
        return Employee::whereHas('resortAdmin', fn ($q) => $q->where('email', "{$login}@demo.thewisdom.ai"))->firstOrFail();
    }

    /** Sign in as a demo login. Controllers read the user in their constructor and Laravel keeps the instance on the route, so drop it. */
    protected function as(string $login): static
    {
        $this->app['auth']->forgetGuards();
        foreach ($this->app['router']->getRoutes()->getRoutes() as $route) {
            $route->flushController();
        }
        return $this->actingAs(ResortAdmin::where('email', "{$login}@demo.thewisdom.ai")->firstOrFail(), 'resort-admin');
    }
}
