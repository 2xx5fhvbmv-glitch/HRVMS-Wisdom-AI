<?php

namespace App\Console\Commands;

use App\Helpers\Common;
use App\Models\Employee;
use App\Models\ProbationaryLearningProgram;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LearningProbationReminders extends Command
{
    protected $signature = 'learning:probation-reminders';

    protected $description = 'Remind probationary employees (and their manager once overdue) about incomplete compulsory training. Was only fired while the employee happened to open the Learning dashboard; deduped via probationary_program_notifications so both paths stay safe together.';

    public function handle()
    {
        $programsByResort = ProbationaryLearningProgram::with('program')->get()->groupBy('resort_id');

        foreach ($programsByResort as $resortId => $required) {
            Employee::where('resort_id', $resortId)
                ->where('status', 'Active')
                ->where(function ($q) {
                    $q->where('employment_type', 'Probationary')
                      ->orWhereIn('probation_status', ['Active', 'Extended']);
                })
                ->chunk(100, function ($employees) use ($resortId, $required) {
                    foreach ($employees as $emp) {
                        $this->remind($emp, $resortId, $required);
                    }
                });
        }
    }

    private function remind($emp, $resortId, $required)
    {
        $completed = DB::table('training_attendance as ta')
            ->join('training_schedules as ts', 'ts.id', '=', 'ta.training_schedule_id')
            ->where('ts.resort_id', $resortId)
            ->whereIn('ts.training_id', $required->pluck('program_id'))
            ->where('ta.employee_id', $emp->id)
            ->where('ta.status', 'Present')
            ->pluck('ts.training_id')->unique()->all();

        $joined = $emp->joining_date ? Carbon::parse($emp->joining_date) : null;

        foreach ($required as $r) {
            if (in_array($r->program_id, $completed)) continue;

            $dueOn = ($joined && $r->completion_days) ? (clone $joined)->addDays((int) $r->completion_days) : null;
            $kind = ($dueOn && $dueOn->isPast()) ? 'overdue' : 'pending';

            // Same tracker + unique key as Learning\DashboardController::sendProbationaryReminders.
            try {
                DB::table('probationary_program_notifications')->insert([
                    'employee_id' => $emp->id,
                    'program_id'  => $r->program_id,
                    'kind'        => $kind,
                    'sent_at'     => now(),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                continue; // already notified
            }

            $name = optional($r->program)->name ?? '—';
            $recipients = [(int) $emp->id];
            if ($kind === 'overdue' && $emp->reporting_to) $recipients[] = (int) $emp->reporting_to;

            try {
                Common::notifyEmployees(
                    $resortId,
                    $recipients,
                    $kind === 'overdue' ? 'Compulsory Training Overdue' : 'Compulsory Training Reminder',
                    $kind === 'overdue'
                        ? 'Your compulsory probation training "' . $name . '" is overdue. Please complete it as soon as possible.'
                        : 'Reminder: please complete your probation training "' . $name . '"' . ($dueOn ? ' by ' . Common::formatDate($dueOn) : '') . '.',
                    'Learning',
                    $r->program_id,
                    'learning-probationary-reminder'
                );
            } catch (\Exception $e) {
                \Log::warning('Probation reminder failed for emp ' . $emp->id . ': ' . $e->getMessage());
            }
        }
    }
}
