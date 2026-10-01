<?php

namespace App\Console\Commands;

use App\Helpers\Common;
use App\Models\Admin;
use Illuminate\Console\Command;

/**
 * Super-admin MFA recovery path of last resort (lost device + lost recovery
 * codes). Requires server shell access, which is the point. The account is
 * forced to re-enrol on its next sign-in (AdminSecurity middleware).
 */
class ResetAdminTwoFactor extends Command
{
    protected $signature = 'admin:2fa-reset {email}';
    protected $description = 'Clear a super-admin account\'s MFA enrolment so it re-enrols at next sign-in';

    public function handle()
    {
        $admin = Admin::where('email', $this->argument('email'))->first();
        if (!$admin) {
            $this->error('No admin with that email.');
            return 1;
        }

        $admin->two_factor_secret = null;
        $admin->two_factor_recovery_codes = null;
        $admin->two_factor_confirmed_at = null;
        $admin->failed_login_attempts = 0;
        $admin->locked_until = null;
        $admin->save();

        Common::alertSuperAdmins($admin, 'Super-admin MFA reset', 'MFA was reset from the server console for ' . $admin->email . '. If this wasn\'t planned, investigate immediately.');
        $this->info('MFA cleared for ' . $admin->email . '. They will be asked to enrol at next sign-in.');
        return 0;
    }
}
