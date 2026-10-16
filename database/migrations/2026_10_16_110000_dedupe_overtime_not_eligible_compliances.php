<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// DutyRosterController wrote an "Over Time Not Eligibile" breach on every roster
// save (reported_on/description were in its firstOrCreate match keys), older
// code wrote them with no employee_id, and the description came out blank
// (" (DR-20 - ) is not eligible…"). Collapse to one row per employee:
// attach no-employee rows by the Emp_id in their description, keep the
// earliest row, rebuild its description, delete the copies.
return new class extends Migration
{
    private const BREACH = 'Over Time Not Eligibile';

    public function up(): void
    {
        $rows = DB::table('compliances')->where('compliance_breached_name', self::BREACH)->where('module_name', 'Time and Attendance')
            ->orderBy('id')->get(['id', 'resort_id', 'employee_id', 'description']);

        $byEmployee = [];
        $unmatched = [];
        foreach ($rows as $r) {
            $empId = $r->employee_id;
            if (!$empId && preg_match('/\(([A-Za-z0-9]+-[A-Za-z0-9]+) - /', (string) $r->description, $m)) {
                $empId = DB::table('employees')->where('resort_id', $r->resort_id)->where('Emp_id', $m[1])->value('id');
            }
            if (!$empId) {
                $unmatched[] = $r->id;
                continue;
            }
            $byEmployee[$r->resort_id . '|' . $empId][] = $r->id;
        }

        $deleted = count($unmatched);
        DB::table('compliances')->whereIn('id', $unmatched)->delete();
        foreach ($byEmployee as $key => $ids) {
            [, $empId] = explode('|', $key);
            $keep = array_shift($ids);
            $emp = DB::table('employees as e')->leftJoin('resort_admins as a', 'a.id', '=', 'e.Admin_Parent_id')
                ->leftJoin('resort_positions as p', 'p.id', '=', 'e.Position_id')->where('e.id', $empId)
                ->first(['e.Emp_id', 'a.first_name', 'a.last_name', 'p.position_title']);
            DB::table('compliances')->where('id', $keep)->update([
                'employee_id' => $empId,
                'description' => trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')) . " ({$emp->Emp_id} - " . ($emp->position_title ?? '-') . ') is not eligible for overtime.',
            ]);
            if ($ids) {
                DB::table('compliances')->whereIn('id', $ids)->delete();
                $deleted += count($ids);
            }
        }
        Log::info('Deduped Over Time Not Eligibile compliances', ['kept' => count($byEmployee), 'deleted' => $deleted, 'unmatched' => count($unmatched)]);
    }

    public function down(): void
    {
        // Not reversible: the removed rows were copies.
    }
};
