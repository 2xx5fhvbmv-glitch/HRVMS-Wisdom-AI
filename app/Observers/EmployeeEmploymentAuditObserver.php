<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\EmployeeEmploymentAuditLog;
use App\Models\ResortPosition;
use App\Models\ResortDepartment;
use App\Models\ResortSection;
use App\Models\ResortDivision;
use Illuminate\Support\Facades\Auth;

/**
 * Listens to Employee::updated and writes one EmployeeEmploymentAuditLog
 * row per tracked field that changed — REGARDLESS of which controller /
 * module triggered the update.
 *
 * Pre-observer, the audit log was only populated by
 * EmployeeController::updateEmploymentData via writeEmploymentAuditLog().
 * Updates coming from Promotion approvals, Salary Increment apply, Exit
 * Clearance status flips, and so on bypassed the log entirely — leading
 * to "No edits recorded yet." while values on the page had clearly
 * changed.
 *
 * The observer covers the same field set as the manual writer, plus
 * basic_salary so promotions / increments are captured.
 */
class EmployeeEmploymentAuditObserver
{
    /**
     * Map of `employees` column → human label used in the audit-log UI.
     * Mirrors EmployeeController::writeEmploymentAuditLog so the two
     * paths emit consistent rows.
     */
    private const TRACKED = [
        'status'              => 'Employment Status',
        'joining_date'        => 'Joining Date',
        'benefit_grid_level'  => 'Benefit Grid Level',
        'tin'                 => 'TIN',
        'probation_end_date'  => 'Probation End Date',
        'contract_type'       => 'Contract Type',
        'termination_date'    => 'Termination Date',
        'Position_id'         => 'Position',
        'Section_id'          => 'Section',
        'Dept_id'             => 'Department',
        'division_id'         => 'Division',
        'reporting_to'        => 'Reporting To',
        'basic_salary'        => 'Basic Salary',
        'basic_salary_currency' => 'Basic Salary Currency',
        'employment_type'     => 'Employment Type',
        // Salary Details tab — the four entitlement toggles + EWT + pension
        // + payment mode all live on this Employee row, and the updateSalary
        // endpoint saves them via Eloquent. Without these in TRACKED, the
        // observer fired but had nothing to log — so HR's toggle flips were
        // silent. (Reported symptom: "I turned Service Charge on but it
        // doesn't show in the Employment Change Log.")
        'entitled_service_charge'  => 'Entitle for Service Charge',
        'entitled_overtime'        => 'Entitle for Overtime',
        'entitled_public_holiday'  => 'Entitle for Public Holiday',
        'ewt_status'               => 'EWT Status',
        'ewt'                      => 'EWT',
        'pension'                  => 'Pension',
        'payment_mode'             => 'Payment Mode',
    ];

    /**
     * Columns whose value is a yes/no flag. Rendered as title-case in the
     * audit-log UI so the column doesn't read "yes → no" all lowercase.
     */
    private const YES_NO_FIELDS = [
        'entitled_service_charge',
        'entitled_overtime',
        'entitled_public_holiday',
        'ewt_status',
        'ewt',
    ];

    public function updated(Employee $employee): void
    {
        $changed = $employee->getDirty();
        if (empty($changed)) {
            return;
        }

        $admin = Auth::guard('resort-admin')->user();
        $changedById = $admin->id ?? null;

        foreach (self::TRACKED as $field => $label) {
            if (!array_key_exists($field, $changed)) {
                continue;
            }
            $oldRaw = $employee->getOriginal($field);
            $newRaw = $changed[$field];

            // No-op guard: if both sides are equivalent after trimming /
            // null-coercion, don't write a log row. Eloquent's getDirty
            // can flag "" vs null as a change which would otherwise add
            // noise.
            $normalize = static fn ($v) => $v === null ? '' : trim((string) $v);
            if ($normalize($oldRaw) === $normalize($newRaw)) {
                continue;
            }

            EmployeeEmploymentAuditLog::create([
                'resort_id'   => $employee->resort_id,
                'employee_id' => $employee->id,
                'field'       => $field,
                'label'       => $label,
                'old_value'   => $this->humanize($field, $oldRaw),
                'new_value'   => $this->humanize($field, $newRaw),
                'changed_by'  => $changedById,
            ]);
        }
    }

    /**
     * Resolve FK ids to their human-readable label so the audit log
     * reads "Senior Accountant" not "47", "Engineering" not "12".
     */
    private function humanize(string $field, $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (in_array($field, self::YES_NO_FIELDS, true)) {
            // Stored as lowercase 'yes'/'no'; title-case for the UI.
            return ucfirst(strtolower((string) $value));
        }
        switch ($field) {
            case 'Position_id':
                return ResortPosition::whereKey($value)->value('position_title') ?? (string) $value;
            case 'Dept_id':
                return ResortDepartment::whereKey($value)->value('name') ?? (string) $value;
            case 'Section_id':
                $s = ResortSection::whereKey($value)->first(['section_title', 'name']);
                return $s ? ($s->section_title ?? $s->name ?? (string) $value) : (string) $value;
            case 'division_id':
                return ResortDivision::whereKey($value)->value('name') ?? (string) $value;
            case 'reporting_to':
                // FK to employees.id. Render the manager's full name.
                $emp = Employee::with('resortAdmin')->find($value);
                return $emp && $emp->resortAdmin ? $emp->resortAdmin->full_name : (string) $value;
            default:
                return (string) $value;
        }
    }
}
