<?php

namespace App\Support\Demo;

use App\Models\Resort;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The Demo ENV resort: one ordinary resort inside the normal database that can
 * be wiped and rebuilt. Everything demo-only checks isDemoResort(), so real
 * resorts never see demo behaviour.
 */
class Demo
{
    private static ?int $resortId = null;
    private static bool $looked = false;

    public static function enabled(): bool
    {
        return (bool) config('demo.enabled');
    }

    /** resorts.id of the demo resort, or null (demo disabled or not created yet). */
    public static function resortId(): ?int
    {
        if (!self::enabled()) {
            return null;
        }
        if (!self::$looked) {
            self::$resortId = DB::table('resorts')->where('resort_id', config('demo.resort_code'))->value('id');
            self::$looked = true;
        }
        return self::$resortId;
    }

    public static function isDemoResort($resortId): bool
    {
        return $resortId !== null && self::resortId() !== null && (int) $resortId === self::resortId();
    }

    /** Forget the cached id (after the resort row is created). */
    public static function refresh(): void
    {
        self::$looked = false;
        self::$resortId = null;
    }

    /**
     * Throws unless it is safe to wipe the demo resort: demo mode on, the resort
     * found by its fixed code AND its demo email (the name is renamed per client,
     * so it is not used), and small enough to be the demo.
     */
    public static function assertSafeToReset(): object
    {
        if (!self::enabled()) {
            throw new RuntimeException('Demo mode is off (DEMO_MODE=false) — refusing to reset.');
        }
        $resort = DB::table('resorts')->where('resort_id', config('demo.resort_code'))->first();
        if (!$resort) {
            throw new RuntimeException('The demo resort (code ' . config('demo.resort_code') . ') does not exist.');
        }
        if ($resort->resort_email !== config('demo.resort_email')) {
            throw new RuntimeException("Resort {$resort->id} has the demo code but not the demo email — refusing to touch it.");
        }
        $employees = DB::table('employees')->where('resort_id', $resort->id)->count();
        if ($employees > config('demo.max_employees')) {
            throw new RuntimeException("Resort {$resort->id} has {$employees} employees — more than a demo ever has. Refusing.");
        }
        return $resort;
    }
}
