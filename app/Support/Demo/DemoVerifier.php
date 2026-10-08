<?php

namespace App\Support\Demo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * `demo:verify` — checks the rebuilt demo actually works. Each slice adds its
 * flow checks (spec §12). A FAIL means the demo should not be shown.
 */
class DemoVerifier
{
    private array $checks = [];

    public function run(): array
    {
        $this->check('Demo resort exists and is safe to reset', function () {
            Demo::assertSafeToReset();
            return true;
        });
        $resortId = Demo::resortId();

        $this->check('Master admin signs in with the demo password', function () use ($resortId) {
            $hash = DB::table('resort_admins')->where('resort_id', $resortId)->where('is_master_admin', 1)->value('password');
            return $hash && Hash::check(config('demo.password'), $hash);
        });
        $this->check('Every module page is on the menu', function () use ($resortId) {
            $pages = DB::table('module_pages')->where('status', 'Active')->whereNull('deleted_at')->count();
            $granted = DB::table('resort_pagewise_permissions')->where('resort_id', $resortId)->count();
            return $granted === $pages ?: "{$granted} of {$pages} pages granted";
        });

        return ['passed' => !collect($this->checks)->contains('ok', false), 'checks' => $this->checks];
    }

    /** $test returns true (pass), or false / a string explaining the failure. */
    private function check(string $name, callable $test): void
    {
        try {
            $result = $test();
        } catch (\Throwable $e) {
            $result = $e->getMessage();
        }
        $this->checks[] = ['name' => $name, 'ok' => $result === true, 'detail' => $result === true ? null : ($result ?: 'failed')];
    }
}
