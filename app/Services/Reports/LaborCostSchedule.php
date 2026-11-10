<?php

namespace App\Services\Reports;

/**
 * Groups Annual Workforce Plan rows into the Schedule of Labor Costs (resorts/partials/documents/labor_cost_schedule).
 * Rows: Department, Position, Grade, Approved Headcount, Planned Headcount, Total Budget ("$1,234.00" or "MVR 1,234.00").
 *
 * Departments are classified by keyword, not by one resort's exact names, so every resort gets the hotel-style split.
 * MVR is converted to USD only when $fx (MVR per 1 USD, from DollertoMVR) is given; MVR and USD are never added otherwise.
 */
class LaborCostSchedule
{
    public const OPS = 'Operated departments';
    public const UND = 'Undistributed operating expenses';
    public const OTH = 'Other departments';

    /** [pattern on the normalised department name, report department, group]; first match wins, order = print order. */
    private const DEPARTMENTS = [
        ['/front office|housekeeping|reservation|guest|rooms?\b|laundry|butler|concierge/', 'Rooms', self::OPS],
        ['/\bf and b\b|food|beverage|kitchen|culinary|restaurant|\bbar\b|banquet|steward/', 'Food & Beverage', self::OPS],
        ['/executive|finance|account|human|\bhr\b|admin|purchas|procure|security|\bit\b|information tech|sales|marketing/', 'Administrative & General', self::UND],
        ['/engineering|maintenance|technical|facilit/', 'Property Operation & Maintenance', self::UND],
    ];
    private const GROUP_ORDER = [self::OPS, self::UND, self::OTH];
    private const MGMT_GRADES = ['GM', 'EXCOM', 'HOD', 'MGR'];

    public static function norm($s): string
    {
        return trim(preg_replace('/\s+/', ' ', str_replace(['&', '/', '-', '.'], [' and ', ' ', ' ', ' '], strtolower((string) $s))));
    }

    /** @return array{groups: string[], tree: array, grp: array, grand: array, approved: int, lines: int} */
    public static function build(array $rows, ?float $fx): array
    {
        $fx = $fx && $fx > 0 ? $fx : null;
        $zero = fn() => ['hc' => 0, 'mh' => 0, 'm' => ['USD' => 0, 'MVR' => 0], 'n' => ['USD' => 0, 'MVR' => 0], 't' => ['USD' => 0, 'MVR' => 0]];
        $add = function (array &$a, int $hc, bool $mg, string $cur, float $v) {
            $a['hc'] += $hc; $a['t'][$cur] += $v;
            if ($mg) { $a['mh'] += $hc; $a['m'][$cur] += $v; } else { $a['n'][$cur] += $v; }
        };

        $tree = []; $grp = []; $grand = $zero(); $approved = 0; $lines = 0;
        foreach (array_values($rows) as $r) {
            $srcDept = trim((string) ($r['Department'] ?? '')) !== '' ? trim((string) $r['Department']) : 'N/A';
            $key = self::norm($srcDept);
            if ($key === 'total') continue;   // the report's own trailing total row, not a department
            $pos = trim((string) ($r['Position'] ?? ''));
            [$dept, $g, $ord] = [$srcDept, self::OTH, 99];
            foreach (self::DEPARTMENTS as $i => [$re, $d, $grpName]) {
                if (preg_match($re, $key)) { [$dept, $g, $ord] = [$d, $grpName, $i]; break; }
            }
            $sub = $srcDept;
            // Grounds staff counted under Rooms/Housekeeping belong to property maintenance.
            if ($g === self::OPS && preg_match('/garden|landscap|grounds/i', $pos)) {
                [$dept, $g, $sub, $ord] = ['Property Operation & Maintenance', self::UND, 'Grounds (from ' . $srcDept . ')', 98];
            }

            $hc = (int) ($r['Planned Headcount'] ?? 0);
            $approved += (int) ($r['Approved Headcount'] ?? 0); $lines++;
            $raw = trim((string) ($r['Total Budget'] ?? 0));
            $cur = stripos($raw, 'MVR') === 0 ? 'MVR' : 'USD';
            $amt = (float) preg_replace('/[^0-9.]/', '', $raw);
            $grade = trim((string) ($r['Grade'] ?? ''));
            $mg = in_array(strtoupper($grade), self::MGMT_GRADES, true);
            $bk = ($fx && $cur === 'MVR') ? 'USD' : $cur;          // bucket the amount is added to
            $v  = ($fx && $cur === 'MVR') ? $amt / $fx : $amt;      // MVR → USD by division (DollertoMVR), never a stored inverse

            $tree[$g][$dept] ??= ['a' => $zero(), 'subs' => []];
            $tree[$g][$dept]['subs'][$sub] ??= ['a' => $zero(), 'rows' => [], 'ord' => $ord];
            $grp[$g] ??= $zero();
            $add($tree[$g][$dept]['subs'][$sub]['a'], $hc, $mg, $bk, $v);
            $add($tree[$g][$dept]['a'], $hc, $mg, $bk, $v);
            $add($grp[$g], $hc, $mg, $bk, $v);
            $add($grand, $hc, $mg, $bk, $v);
            $tree[$g][$dept]['subs'][$sub]['rows'][] = ['pos' => $pos, 'grade' => $grade, 'mg' => $mg, 'hc' => $hc, 'cur' => $cur, 'amt' => $amt, 'v' => $v];
        }

        // Print order: groups fixed, mapped departments in DEPARTMENTS order, the rest alphabetical; sub-departments by map order.
        $deptOrder = array_column(self::DEPARTMENTS, 1);
        foreach ($tree as &$depts) {
            uksort($depts, function ($a, $b) use ($deptOrder) {
                $ia = array_search($a, $deptOrder, true); $ib = array_search($b, $deptOrder, true);
                return ($ia === false ? 99 : $ia) <=> ($ib === false ? 99 : $ib) ?: strcasecmp($a, $b);
            });
            foreach ($depts as &$d) { uasort($d['subs'], fn($a, $b) => $a['ord'] <=> $b['ord']); }
            unset($d);
        }
        unset($depts);

        return [
            'groups'   => array_values(array_filter(self::GROUP_ORDER, fn($g) => isset($tree[$g]))),
            'tree'     => $tree, 'grp' => $grp, 'grand' => $grand, 'approved' => $approved, 'lines' => $lines,
        ];
    }
}
