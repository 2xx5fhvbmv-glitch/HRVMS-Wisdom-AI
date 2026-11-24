<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only production checklist from the security audit (run-4 §5). Changes nothing; prints PASS / WARN / FAIL per check
 * and what to do about a WARN/FAIL. Never prints a secret value. Run on each server:  php artisan security:prod-check
 */
class SecurityProdCheck extends Command
{
    protected $signature = 'security:prod-check';

    protected $description = 'Read-only production security checklist (audit run-4 §5)';

    private int $fails = 0;

    public function handle(): int
    {
        $this->testAccounts();
        $this->migrations();
        $this->modulePages();
        $this->publicFolders();
        $this->environment();
        $this->readonlyUser();
        $this->oauthClients();
        $this->superAdmin();
        $this->line('');
        $this->line('MANUAL (needs a browser): log in, copy the session cookie, log out, replay the cookie → must be logged out. '
            . 'Change your password in one browser → the other browser must be signed out on its next request.');

        $this->line('');
        $this->fails ? $this->error("{$this->fails} check(s) need attention.") : $this->info('All automatic checks passed.');
        return $this->fails ? self::FAILURE : self::SUCCESS;
    }

    private function result(string $status, string $check, string $detail = ''): void
    {
        if ($status !== 'PASS') {
            $this->fails++;
        }
        $line = str_pad($status, 5) . ' ' . $check . ($detail !== '' ? " — {$detail}" : '');
        $status === 'PASS' ? $this->info($line) : ($status === 'WARN' ? $this->warn($line) : $this->error($line));
    }

    /** 1. Accounts created by test migrations/seeders with passwords that are in the repo. */
    private function testAccounts(): void
    {
        $active = fn ($q) => $q->whereNull('deleted_at')->whereRaw('LOWER(status) = ?', ['active']);
        $groups = [
            'Clinic QA accounts (migration 2026_08_09_233000)' => DB::table('resort_admins')->whereIn('email', ['clinic-employee-test@example.test', 'clinic-manager-test@example.test']),
            'ImportWisdomAiStaffSeeder accounts (testemp.*@wisdomai.test)' => DB::table('resort_admins')->where('email', 'like', 'testemp.%@wisdomai.test'),
            'PayrollSupervisorSeeder account (sarah.thompson@resort.com)' => DB::table('resort_admins')->where('email', 'sarah.thompson@resort.com'),
        ];
        foreach ($groups as $label => $q) {
            $n = (clone $q)->where($active)->count();
            $this->result($n ? 'FAIL' : 'PASS', $label, $n ? "{$n} active — their passwords are in the repo: deactivate them or reset the passwords" : 'none active');
        }
    }

    /** 2. Security migrations ran, and the resort_admins.type repair holds. */
    private function migrations(): void
    {
        foreach (['2026_10_29_130000', '2026_11_10_100000', '2026_11_10_110000', '2026_11_21_100000', '2026_11_24_120000', '2026_11_24_150000'] as $prefix) {
            $ran = DB::table('migrations')->where('migration', 'like', $prefix . '%')->exists();
            $this->result($ran ? 'PASS' : 'FAIL', "Migration {$prefix}_*", $ran ? 'ran' : 'NOT run — php artisan migrate --path=database/migrations/<that file>');
        }
        $col = collect(DB::select("SHOW COLUMNS FROM resort_admins LIKE 'type'"))->first();
        $this->result(($col->Default ?? null) === 'sub' ? 'PASS' : 'FAIL', 'resort_admins.type default', 'default = ' . var_export($col->Default ?? null, true));
        $supers = DB::table('resort_admins')->where('type', 'super')->where(fn ($q) => $q->whereNull('is_master_admin')->orWhere('is_master_admin', 0))->whereNull('deleted_at')->count();
        $this->result($supers ? 'FAIL' : 'PASS', "Non-master accounts with type 'super'", (string) $supers);
    }

    /** 3. module_pages rows that only a seeder creates — gates naming them fail open without the row. */
    private function modulePages(): void
    {
        foreach (['resort.sitesettings', 'resort.benifitgrid.index', 'resort.emailconfig.index', 'resort.masterimport.index'] as $route) {
            $ok = DB::table('module_pages')->where('internal_route', $route)->exists();
            $this->result($ok ? 'PASS' : 'FAIL', "module_pages row {$route}", $ok ? 'present' : 'missing — the permission gate naming it never denies');
        }
    }

    /** 4. Public folders: nothing executable or HTML-like uploaded where the web server serves it. */
    private function publicFolders(): void
    {
        foreach (['uploads' => public_path('uploads'), 'storage' => public_path('storage')] as $name => $dir) {
            if (!is_dir($dir)) {
                $this->result('PASS', "public/{$name}", 'does not exist');
                continue;
            }
            $bad = 0;
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if ($f->isFile() && preg_match('/\.(php\d?|phtml|phar|html?|svg|js|xml)$/i', $f->getFilename())) {
                    $bad++;
                }
            }
            $this->result($bad ? 'FAIL' : 'PASS', "public/{$name} scripts/HTML/SVG files", $bad ? "{$bad} found — remove them" : 'none');
        }
        $this->line('      Also confirm in nginx: no PHP execution under /uploads and /storage, and `add_header X-Content-Type-Options nosniff` there.');
    }

    /** 5. Environment values (presence/shape only, never the values of secrets). */
    private function environment(): void
    {
        $this->result(config('broadcasting.default') === 'pusher' ? 'PASS' : 'WARN', 'BROADCAST_DRIVER', (string) config('broadcasting.default'));
        $key = (string) config('app.file_encryption_key');   // env() is null once config is cached
        $this->result($key !== '' ? 'PASS' : 'FAIL', 'ENCRYPTION_KEY set', $key !== '' ? 'non-empty' : 'EMPTY — encrypted files cannot be read');
        $url = (string) config('app.url');
        $this->result(str_starts_with($url, 'https://') ? 'PASS' : 'FAIL', 'APP_URL', $url . ' (reset links and cookies use it)');
        $this->result(!config('app.debug') ? 'PASS' : 'FAIL', 'APP_DEBUG off', config('app.debug') ? 'ON — stack traces are shown to users' : 'off');
        $this->result(config('session.secure') ? 'PASS' : 'FAIL', 'Session cookie Secure', config('session.secure') ? 'on' : 'off');
        $this->result('PASS', 'Cache / session drivers', 'cache=' . config('cache.default') . ', session=' . config('session.driver') . ' (use a shared store if more than one app server)');
    }

    /** 6. The Wisdom AI read-only DB user must not be able to write. */
    private function readonlyUser(): void
    {
        try {
            $grants = collect(DB::connection('mysql_readonly')->select('SHOW GRANTS FOR CURRENT_USER()'))->map(fn ($r) => strtoupper(implode(' ', (array) $r)))->implode(' | ');
            $writes = preg_match('/\b(ALL PRIVILEGES|INSERT|UPDATE|DELETE|DROP|ALTER|CREATE)\b/', $grants);
            $this->result($writes ? 'FAIL' : 'PASS', 'DB_READONLY user is SELECT-only', $writes ? 'has write grants — restrict it to SELECT' : 'SELECT only');
        } catch (\Throwable $e) {
            $this->result('WARN', 'DB_READONLY user', 'could not check: ' . $e->getMessage());
        }
    }

    /** 7. Passport clients with no provider can mint tokens for any guard. */
    private function oauthClients(): void
    {
        if (!Schema::hasTable('oauth_clients') || !Schema::hasColumn('oauth_clients', 'provider')) {
            $this->result('WARN', 'oauth_clients', 'table/provider column missing');
            return;
        }
        $n = DB::table('oauth_clients')->whereNull('provider')->where('revoked', false)->count();
        $this->result($n ? 'FAIL' : 'PASS', 'oauth_clients with NULL provider (not revoked)', (string) $n);
    }

    /** 8. The original super-admin and old audit payloads. */
    private function superAdmin(): void
    {
        $sa = DB::table('admins')->where('email', 'superadmin@wisdom.ai')->whereNull('deleted_at')->first();
        if (!$sa) {
            $this->result('PASS', 'superadmin@wisdom.ai', 'not present / deleted');
        } else {
            $this->result($sa->two_factor_confirmed_at ? 'PASS' : 'FAIL', 'superadmin@wisdom.ai MFA', $sa->two_factor_confirmed_at ? 'enrolled' : 'NOT enrolled — enroll or disable the account');
            $this->line('      Confirm its password was rotated after the audit (cannot be checked automatically).');
        }
        $leaked = DB::table('admin_audit_logs')->where(fn ($q) => $q->where('payload', 'like', '%old_pass%')->orWhere('payload', 'like', '%password_confirm%')->orWhere('payload', 'like', '%"password"%'))->count();
        $this->result($leaked ? 'FAIL' : 'PASS', 'admin_audit_logs rows containing password fields', $leaked ? "{$leaked} — purge them (and from backups)" : '0');
    }
}
