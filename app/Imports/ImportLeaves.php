<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\LeaveCategory;
use App\Models\EmployeeLeave;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportLeaves implements ToModel, WithHeadingRow
{
    protected $resort_id;

    /** Allowed status values (saved to DB). */
    protected const ALLOWED_STATUSES = ['Approved', 'Rejected', 'Pending'];

    public function __construct($resort_id)
    {
        $this->resort_id = $resort_id;
    }

    /**
     * Get row value by key; support slug-style keys and trim strings.
     */
    protected function getRowValue(array $row, string $key): mixed
    {
        $value = $row[$key] ?? $row[\Illuminate\Support\Str::slug($key, '_')] ?? null;
        if (is_string($value)) {
            return trim($value);
        }
        return $value;
    }

    /**
     * Parse date from Excel (serial number or dd-mm-yyyy string).
     */
    protected function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value) && strtolower(trim($value)) === 'dd-mm-yyyy') {
            return null;
        }
        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        try {
            return Carbon::createFromFormat('d-m-Y', $value)->format('Y-m-d');
        } catch (\Exception $e) {
            try {
                return Carbon::parse($value)->format('Y-m-d');
            } catch (\Exception $e2) {
                return null;
            }
        }
    }

    /**
     * Resolve leave category by exact or case-insensitive leave_type match.
     */
    protected function findLeaveCategory(string $categoryInput): ?LeaveCategory
    {
        $categoryInput = trim($categoryInput);
        if ($categoryInput === '') {
            return null;
        }
        $found = LeaveCategory::where('resort_id', $this->resort_id)
            ->where('leave_type', $categoryInput)
            ->first();
        if ($found) {
            return $found;
        }
        return LeaveCategory::where('resort_id', $this->resort_id)
            ->whereRaw('LOWER(TRIM(leave_type)) = ?', [strtolower($categoryInput)])
            ->first();
    }

    /**
     * Normalize status to one of Approved, Rejected, Pending.
     */
    protected function normalizeStatus(?string $status): ?string
    {
        if ($status === null || trim($status) === '') {
            return null;
        }
        $lower = strtolower(trim($status));
        foreach (self::ALLOWED_STATUSES as $allowed) {
            if (strtolower($allowed) === $lower) {
                return $allowed;
            }
        }
        return null;
    }

    public function model(array $row)
    {
        $fromDate = $this->parseDate($this->getRowValue($row, 'from_date') ?? $row['from_date'] ?? null);
        $toDate   = $this->parseDate($this->getRowValue($row, 'to_date') ?? $row['to_date'] ?? null);
        if ($fromDate === null || $toDate === null) {
            return null;
        }

        $employeeIdRaw = $this->getRowValue($row, 'employee_id') ?? $row['employee_id'] ?? '';
        preg_match('/\((.*?)\)/', (string) $employeeIdRaw, $emp_id);
        if (!isset($emp_id[1])) {
            return null;
        }
        $empCode = trim($emp_id[1]);

        $leaveCategoryInput = $this->getRowValue($row, 'leave_category') ?? $row['leave_category'] ?? '';
        $leaveCategory = $this->findLeaveCategory(is_string($leaveCategoryInput) ? $leaveCategoryInput : '');
        if (!$leaveCategory) {
            return null;
        }

        $employee = Employee::where('resort_id', $this->resort_id)
            ->where('Emp_id', $empCode)
            ->first();
        if (!$employee) {
            return null;
        }

        $reason    = $this->getRowValue($row, 'leave_reason') ?? $row['leave_reason'] ?? null;
        $totalDays = $this->getRowValue($row, 'total_days') ?? $row['total_days'] ?? null;
        if ($totalDays !== null && $totalDays !== '') {
            $totalDays = is_numeric($totalDays) ? (int) $totalDays : null;
        } else {
            $totalDays = null;
        }

        $statusInput = $this->getRowValue($row, 'status') ?? $row['status'] ?? null;
        $status = $this->normalizeStatus(is_string($statusInput) ? $statusInput : (string) $statusInput);
        if ($status === null && $statusInput !== null && trim((string) $statusInput) !== '') {
            $status = 'Pending';
        }
        if ($status === null) {
            $status = 'Pending';
        }

        return new EmployeeLeave([
            'resort_id'         => $this->resort_id,
            'emp_id'            => $employee->id,
            'leave_category_id' => $leaveCategory->id,
            'from_date'         => $fromDate,
            'to_date'           => $toDate,
            'reason'            => $reason,
            'total_days'        => $totalDays,
            'status'            => $status,
        ]);
    }
}
