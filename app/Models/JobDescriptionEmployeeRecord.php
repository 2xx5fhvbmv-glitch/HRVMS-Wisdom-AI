<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class JobDescriptionEmployeeRecord extends Model
{
    use HasFactory;

    protected $table = 'job_description_employee_records';

    protected $fillable = [
        'resort_id',
        'job_description_id',
        'employee_id',
        'employer_name',
        'employer_address',
        'employer_nationality',
        'employer_type_of_work',
        'employer_signatory_employee_id',
        'employer_signatory_name',
        'employer_signatory_designation',
        'employer_signatory_id_card_number',
        'employee_full_name',
        'employee_permanent_address',
        'employee_current_address',
        'employee_id_card_number',
        'employee_dob',
        'employee_nationality',
        'employer_signature_path',
        'employee_signature_path',
        'status',
        'decline_reason',
        'sent_at',
        'signed_at',
        'resend_count',
        'pdf_path',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'employee_dob' => 'date',
        'sent_at' => 'datetime',
        'signed_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        self::saving(function ($model) {
            $user = Auth::guard('resort-admin')->user() ?? Auth::guard('api')->user();
            if (!$user) return;
            if (!$model->exists) {
                $model->created_by = $user->id;
            }
            $model->modified_by = $user->id;
        });

        // Compliance flag follows the record immediately (issue, sign,
        // decline, resend) — the People > Compliance run re-checks too.
        self::saved(function ($model) {
            if ($model->wasRecentlyCreated || $model->wasChanged('status')) {
                try {
                    self::syncCompliance($model->resort_id, $model->employee_id);
                } catch (\Throwable $e) {
                    \Log::warning('Job description compliance sync failed: ' . $e->getMessage());
                }
            }
        });
    }

    public const COMPLIANCE_RULES = [
        'Pending'  => ['Job Description Not Signed', 'has not signed'],
        'Declined' => ['Job Description Declined', 'has declined'],
    ];

    /**
     * Open one "Job Description Not Signed" / "Job Description Declined"
     * breach per employee while any of their records is Pending / Declined;
     * resolve it once none is. A manually dismissed row is not re-raised.
     * Returns the breach names still active (for the compliance run's
     * auto-resolve sweep).
     */
    public static function syncCompliance($resortId, $employeeId): array
    {
        $active = [];
        // Only Active employees are flagged (founder, 2026-10-15); an Inactive one's
        // open breach resolves exactly as if the JD had been signed.
        $isActive = \App\Models\Employee::where('resort_id', $resortId)->where('id', $employeeId)->value('status') === 'Active';
        foreach (self::COMPLIANCE_RULES as $status => [$breach, $verb]) {
            $open = Compliance::where('resort_id', $resortId)->where('employee_id', $employeeId)
                ->where('module_name', 'Job Description')->where('compliance_breached_name', $breach)
                ->where('status', 'Breached');
            $record = self::with(['employee.resortAdmin', 'employee.position'])
                ->where('resort_id', $resortId)->where('employee_id', $employeeId)
                ->where('status', $status)->latest('id')->first();

            if (!$record || !$isActive) {
                // Same resolved marking as ComplianceController::resolveStaleBreaches().
                foreach ($open->where('Dismissal_status', 'Pending')->get() as $row) {
                    $row->status = 'Resolved';
                    $row->Dismissal_status = 'Resolved';
                    $row->save();
                }
                continue;
            }

            $active[] = $breach;
            if ($open->exists()) {
                continue;
            }
            $emp = $record->employee;
            $name = trim(($emp->resortAdmin->first_name ?? '') . ' ' . ($emp->resortAdmin->last_name ?? '')) ?: $record->employee_full_name;
            $issued = ($record->sent_at ?? $record->created_at)->format('d M Y');
            Compliance::create([
                'resort_id'                => $resortId,
                'employee_id'              => $employeeId,
                'module_name'              => 'Job Description',
                'compliance_breached_name' => $breach,
                'description'              => "Employee {$name} (" . ($emp->position->position_title ?? '-') . ") {$verb} the job description issued on {$issued}.",
                'reported_on'              => now(),
                'status'                   => 'Breached',
            ]);
        }
        return $active;
    }

    public function jobDescription()
    {
        return $this->belongsTo(JobDescription::class, 'job_description_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
