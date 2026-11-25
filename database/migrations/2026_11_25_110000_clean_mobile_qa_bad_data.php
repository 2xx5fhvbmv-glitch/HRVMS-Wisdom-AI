<?php

use App\Helpers\Common;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Data cleanup from the mobile QA report (25 Nov 2026). Every rule is narrow, and every row it changes or removes is
 * written to the log with its old value (search the log for "mobile-qa-cleanup") so anything can be put back.
 *
 *  1. Congratulations: one per person per announcement (duplicates from repeated taps), then a unique index so it
 *     can't happen again (the endpoint now uses firstOrCreate).
 *  2. Announcements submitted more than once by a double click (same resort, employee, title and creator within
 *     60 seconds): the later copies are archived, not deleted.
 *  3. Emergency-contact names that contain no letters at all (a phone number typed into a name field) are cleared.
 *  4. Phone numbers stored as bare 960XXXXXXX get their "+", and valid numbers are stored in one format
 *     (+ and digits). Numbers that are still invalid (e.g. too many digits) are left for HR to correct.
 *  5. Monthly check-ins holding only placeholder/test text are removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->congratulations();
        $this->doubleSubmittedAnnouncements();
        $this->emergencyContactNames();
        $this->phones();
        $this->placeholderCheckIns();
    }

    private function log(string $what, array $data): void
    {
        Log::info("mobile-qa-cleanup: {$what}", $data);
    }

    private function congratulations(): void
    {
        $dupes = DB::table('announcement_notification')
            ->select('announcement_id', 'employee_id', DB::raw('MIN(id) keep_id'), DB::raw('COUNT(*) n'))
            ->groupBy('announcement_id', 'employee_id')->having('n', '>', 1)->get();
        foreach ($dupes as $d) {
            $rows = DB::table('announcement_notification')->where('announcement_id', $d->announcement_id)
                ->where('employee_id', $d->employee_id)->where('id', '!=', $d->keep_id)->get();
            $this->log('duplicate congratulation removed', ['rows' => $rows->toArray()]);
            DB::table('announcement_notification')->whereIn('id', $rows->pluck('id'))->delete();
        }
        $indexes = collect(DB::select('SHOW INDEX FROM announcement_notification'))->pluck('Key_name');
        if (!$indexes->contains('announcement_notification_once')) {
            Schema::table('announcement_notification', function (Blueprint $t) {
                $t->unique(['announcement_id', 'employee_id'], 'announcement_notification_once');
            });
        }
    }

    private function doubleSubmittedAnnouncements(): void
    {
        $rows = DB::table('announcement')->where('archived', 0)
            ->orderBy('resort_id')->orderBy('employee_id')->orderBy('title')->orderBy('created_by')->orderBy('created_at')->orderBy('id')
            ->get(['id', 'resort_id', 'employee_id', 'title', 'created_by', 'status', 'created_at']);
        $prev = null;
        foreach ($rows as $r) {
            $same = $prev && $prev->resort_id === $r->resort_id && $prev->employee_id === $r->employee_id
                && (string) $prev->title === (string) $r->title && $prev->created_by === $r->created_by
                && strtotime($r->created_at) - strtotime($prev->created_at) <= 60;
            if ($same) {
                $this->log('double-submitted announcement archived', ['id' => $r->id, 'kept' => $prev->id, 'status' => $r->status]);
                DB::table('announcement')->where('id', $r->id)->update(['archived' => 1, 'updated_at' => now()]);
                continue;   // compare the next copy against the one we kept
            }
            $prev = $r;
        }
    }

    private function emergencyContactNames(): void
    {
        foreach (['emg_cont_first_name', 'emg_cont_last_name'] as $col) {
            $rows = DB::table('employees')->whereNotNull($col)->where($col, '!=', '')
                ->whereRaw("{$col} NOT REGEXP '[[:alpha:]]'")->get(['id', $col]);
            foreach ($rows as $r) {
                $this->log('emergency-contact name without letters cleared', ['employee_id' => $r->id, 'column' => $col, 'old' => $r->$col]);
                DB::table('employees')->where('id', $r->id)->update([$col => null]);
            }
        }
    }

    private function phones(): void
    {
        $targets = [['resort_admins', 'personal_phone'], ['employees', 'emg_cont_no'], ['employees', 'emg_cont_alt_no']];
        foreach ($targets as [$table, $col]) {
            foreach (DB::table($table)->whereNotNull($col)->where($col, '!=', '')->get(['id', $col]) as $r) {
                $old = (string) $r->$col;
                $digits = preg_replace('/\D/', '', $old);
                $candidate = (!str_starts_with(trim($old), '+') && str_starts_with($digits, '960') && strlen($digits) === 10) ? '+' . $digits : $old;
                $new = Common::normalizePhone($candidate);
                if ($new !== null && $new !== $old) {
                    $this->log('phone normalised', ['table' => $table, 'id' => $r->id, 'column' => $col, 'old' => $old, 'new' => $new]);
                    DB::table($table)->where('id', $r->id)->update([$col => $new]);
                }
            }
        }
    }

    private function placeholderCheckIns(): void
    {
        $rows = DB::table('monthly_checking_models')->where(function ($q) {
            $q->where('Area_of_Discussion', 'like', 'not@required%')
              ->orWhere('Area_of_Discussion', 'Need to be filled later')
              ->orWhere(fn ($x) => $x->where('Area_of_Discussion', 'test')->where('Area_of_Improvement', 'test'));
        })->get();
        if ($rows->isNotEmpty()) {
            $this->log('placeholder monthly check-ins removed', ['rows' => $rows->toArray()]);
            DB::table('monthly_checking_models')->whereIn('id', $rows->pluck('id'))->delete();
        }
    }

    public function down(): void
    {
        // Data changes are logged ("mobile-qa-cleanup") rather than reversed automatically; only the index is dropped.
        $indexes = collect(DB::select('SHOW INDEX FROM announcement_notification'))->pluck('Key_name');
        if ($indexes->contains('announcement_notification_once')) {
            Schema::table('announcement_notification', fn (Blueprint $t) => $t->dropUnique('announcement_notification_once'));
        }
    }
};
