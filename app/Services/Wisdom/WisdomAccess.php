<?php

namespace App\Services\Wisdom;

use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;

/**
 * Wisdom AI — access-control model.
 *
 * Current rule (2026-11-13): only the HR department's EXCOM (HR Director) and HOD get access (tier FULL);
 * every other user is NONE. The blueprint below describes the tiers kept in code for when access widens again:
 *
 *   TIER 1 — HR Director / HR Manager          → FULL
 *            Full database access (all modules, all employee records) +
 *            policy & employment-law knowledge.
 *
 *   TIER 3 — General Manager                    → GM (moderate)
 *            All databases EXCEPT payroll/compensation, which is fully
 *            restricted. Plus policy & employment law.
 *
 *   TIER 2 — HOD / EXCOM / Managers (non-HR)    → LEADER (scoped)
 *            Policy & law, plus the data catalog (list_datasets / query_data)
 *            limited to their own department; no pay; none of the older
 *            resort-wide tools.
 *
 *   Finance (rank 7, or Finance-dept EXCOM/HOD) → FINANCE
 *            Policy & law, plus the catalog's payroll / advance datasets
 *            resort-wide (amounts yes; names and personal data no).
 *
 *   Everyone else (Supervisors, Line workers, …) → NONE
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
    const TIER_LEADER  = 'leader';  // HOD / EXCOM / Managers outside HR — own department, catalog only
    const TIER_FINANCE = 'finance'; // Finance (rank 7) — payroll catalog datasets, no personal data
    const TIER_POLICY = 'policy';  // kept for old references; no longer assigned
    const TIER_NONE   = 'none';    // no access

    /**
     * Resolve the tier for the currently logged-in resort user.
     */
    public static function tier(): string
    {
        // Decision 2026-11-13: Wisdom AI is open ONLY to the HR department's Director (EXCOM, rank 1) and HOD (rank 2).
        // Everyone else (other HR staff, GM, resort master admin, other departments' heads, Finance) has no access.
        // The GM / leader / finance tiers stay defined (prompt blocks, catalog rules) so access can be widened again
        // by changing this method alone.
        $user = Auth::guard('resort-admin')->user();
        $emp = $user ? ($user->getEmployee ?? null) : null;
        if (!$emp) {
            return self::TIER_NONE;
        }

        if (in_array((int) $emp->rank, [1, 2], true) && Common::isHRDepartment($emp->Dept_id ?? null)) {
            return self::TIER_FULL;
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
            // Leader / Finance: only the catalog tools (list_datasets / query_data), never the resort-wide ones.
            'catalog_only' => in_array($tier, [self::TIER_LEADER, self::TIER_FINANCE], true),
            'resort_id'    => $user->resort_id,
            'resort_name'  => $resortName,
            'user_name'    => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'there',
            'role_label'   => $roleLabel,
            'dept_name'    => $deptName,
            'today'        => now()->toDateString(),
        ] + ($tier === self::TIER_LEADER ? ['dept_ids' => Common::getScopedDepartmentIds() ?? []] : []);
    }

    public static function tierLabel(string $tier): string
    {
        switch ($tier) {
            case self::TIER_FULL:   return 'HR — Full Access';
            case self::TIER_GM:     return 'General Manager — Payroll Restricted';
            case self::TIER_LEADER:  return 'Department Leader — Own Department';
            case self::TIER_FINANCE: return 'Finance — Payroll Data';
            case self::TIER_POLICY: return 'Policy & Employment Law';
            default:                return 'No Access';
        }
    }
}
