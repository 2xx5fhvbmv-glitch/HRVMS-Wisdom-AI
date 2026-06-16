<?php

namespace App\Services\Wisdom;

use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;

/**
 * Wisdom AI — access-control model.
 *
 * Implements the three-tier access blueprint for the in-app HR chatbot:
 *
 *   TIER 1 — HR Director / HR Manager          → FULL
 *            Full database access (all modules, all employee records) +
 *            policy & employment-law knowledge.
 *
 *   TIER 3 — General Manager                    → GM (moderate)
 *            All databases EXCEPT payroll/compensation, which is fully
 *            restricted. Plus policy & employment law.
 *
 *   TIER 2 — HOD / EXCOM / Managers (non-HR)    → POLICY (limited)
 *            No database access at all. Company policy & employment law
 *            questions only.
 *
 *   Everyone else (Supervisors, Line workers, Finance, …) → NONE
 *            Chatbot is hidden; endpoints reject them.
 *
 * Rank map (config/settings.php → Position_Rank):
 *   1 = EXCOM, 2 = HOD, 3 = HR, 4 = MGR, 5 = SUP,
 *   6 = LINE WORKERS, 7 = Finance, 8 = GM
 */
class WisdomAccess
{
    const TIER_FULL   = 'full';    // HR Director / HR Manager
    const TIER_GM     = 'gm';      // General Manager (no payroll)
    const TIER_POLICY = 'policy';  // HOD / EXCOM / Managers — policy & law only
    const TIER_NONE   = 'none';    // no access

    /**
     * Resolve the tier for the currently logged-in resort user.
     */
    public static function tier(): string
    {
        $user = Auth::guard('resort-admin')->user();
        if (!$user) {
            return self::TIER_NONE;
        }

        // Platform super / master admins get the full HR experience.
        if (($user->type ?? null) === 'super' || ($user->is_master_admin ?? 0)) {
            return self::TIER_FULL;
        }

        $emp = $user->getEmployee ?? null;
        if (!$emp) {
            return self::TIER_NONE;
        }

        $rank = (int) $emp->rank;
        $isHR = Common::isHRDepartment($emp->Dept_id ?? null);

        // TIER 1 — HR Director / HR Manager (HR rank, or HR-department head/excom)
        if ($rank === 3) {
            return self::TIER_FULL;
        }
        if (in_array($rank, [1, 2], true) && $isHR) {
            return self::TIER_FULL;
        }

        // TIER 3 — General Manager
        if ($rank === 8) {
            return self::TIER_GM;
        }

        // TIER 2 — HOD / EXCOM / Managers in any non-HR department
        if (in_array($rank, [1, 2, 4], true)) {
            return self::TIER_POLICY;
        }

        return self::TIER_NONE;
    }

    public static function hasAccess(): bool
    {
        return self::tier() !== self::TIER_NONE;
    }

    /** Can this tier query operational databases at all? */
    public static function canUseDatabase(string $tier): bool
    {
        return in_array($tier, [self::TIER_FULL, self::TIER_GM], true);
    }

    /** Can this tier see payroll / salary / compensation data? */
    public static function canAccessPayroll(string $tier): bool
    {
        return $tier === self::TIER_FULL;
    }

    /**
     * Build the full context array used by the prompt builder and tools.
     * Returns null when the user has no access.
     */
    public static function context(): ?array
    {
        $tier = self::tier();
        if ($tier === self::TIER_NONE) {
            return null;
        }

        $user = Auth::guard('resort-admin')->user();
        $emp  = $user->getEmployee ?? null;

        $rankMap   = config('settings.Position_Rank', []);
        $rank      = $emp ? (int) $emp->rank : null;
        $roleLabel = $rank !== null && isset($rankMap[$rank]) ? $rankMap[$rank] : 'Administrator';

        $resort     = $user->resort ?? null;
        $resortName = $resort->resort_name ?? config('app.name', 'the resort');

        $deptName = $emp && $emp->department ? $emp->department->name : null;

        return [
            'tier'         => $tier,
            'tier_label'   => self::tierLabel($tier),
            'can_db'       => self::canUseDatabase($tier),
            'can_payroll'  => self::canAccessPayroll($tier),
            'resort_id'    => $user->resort_id,
            'resort_name'  => $resortName,
            'user_name'    => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'there',
            'role_label'   => $roleLabel,
            'dept_name'    => $deptName,
            'today'        => now()->toDateString(),
        ];
    }

    public static function tierLabel(string $tier): string
    {
        switch ($tier) {
            case self::TIER_FULL:   return 'HR — Full Access';
            case self::TIER_GM:     return 'General Manager — Payroll Restricted';
            case self::TIER_POLICY: return 'Policy & Employment Law';
            default:                return 'No Access';
        }
    }
}
