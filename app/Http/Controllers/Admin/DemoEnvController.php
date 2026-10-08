<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunDemoReset;
use App\Support\Demo\Demo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Super-admin "Demo ENV" page: status of the demo resort and the reset button.
 * Exists only when DEMO_MODE=true (404 otherwise); super admins only; the
 * reset route also requires a fresh password + MFA check (admin.reauth) and
 * the typed phrase. Each request is logged by the AdminSecurity middleware.
 */
class DemoEnvController extends Controller
{
    const PHRASE = 'RESET DEMO ENV';

    public function index()
    {
        $this->guard();
        return $this->render();
    }

    public function reset(Request $request)
    {
        $this->guard();
        if (trim((string) $request->input('confirm')) !== self::PHRASE) {
            return $this->render([['danger', 'Type ' . self::PHRASE . ' exactly to confirm the reset.']]);
        }
        if (in_array(Cache::get('demo:reset_status')['state'] ?? null, ['queued', 'running'], true)) {
            return $this->render([['warning', 'A reset is already in progress.']]);
        }
        Cache::forever('demo:reset_status', ['state' => 'queued', 'started_at' => null, 'updated_at' => now()->toDateTimeString(), 'message' => 'Waiting for the queue worker…']);
        RunDemoReset::dispatch();

        return $this->render();
    }

    private function guard(): void
    {
        abort_unless(Demo::enabled(), 404);
        abort_unless((Auth::guard('admin')->user()->type ?? null) === 'super', 403);
    }

    private function render(array $notices = [])
    {
        $resortId = Demo::resortId();
        $status = Cache::get('demo:reset_status');
        return view('admin.demo_env.index', [
            'notices'  => $notices,
            'resort'   => $resortId ? DB::table('resorts')->find($resortId) : null,
            'counts'   => $resortId ? [
                'employees' => DB::table('employees')->where('resort_id', $resortId)->count(),
                'logins'    => DB::table('resort_admins')->where('resort_id', $resortId)->count(),
            ] : null,
            'status'   => $status,
            'busy'     => in_array($status['state'] ?? null, ['queued', 'running'], true),
            'last'     => Cache::get('demo:last_reset'),
            'phrase'   => self::PHRASE,
        ]);
    }
}
