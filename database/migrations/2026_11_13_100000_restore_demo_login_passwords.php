<?php

use App\Support\Demo\Demo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Demo ENV: hr@ (demo login domain) had been given another password from inside the app, so the team could not sign
 * in with DEMO_PASSWORD. Put every named demo login of the demo resort back on DEMO_PASSWORD. ResortAdmin now
 * refuses such changes. Does nothing unless DEMO_MODE is on and DEMO_PASSWORD is set.
 */
return new class extends Migration
{
    public function up(): void
    {
        $password = (string) config('demo.password');
        $resortId = Demo::resortId();
        if (!Demo::enabled() || $password === '' || !$resortId) {
            return;
        }
        $hash = Hash::make($password);
        DB::table('resort_admins')->where('resort_id', $resortId)->whereNull('deleted_at')
            ->where('email', 'like', '%@' . config('demo.login_domain'))
            ->get(['id', 'email', 'password'])
            ->filter(fn ($a) => Demo::isLogin($a->email) && !Hash::check($password, (string) $a->password))
            ->each(fn ($a) => DB::table('resort_admins')->where('id', $a->id)->update(['password' => $hash, 'must_change_password' => 0, 'updated_at' => now()]));
    }

    public function down(): void
    {
        // The replaced passwords were never meant to be on shared demo logins.
    }
};
