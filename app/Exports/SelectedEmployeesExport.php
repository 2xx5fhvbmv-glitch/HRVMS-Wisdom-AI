<?php

namespace App\Exports;

use App\Models\Employee;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

/**
 * Wide-format export covering every section of the employee profile
 * the user sees on /resort/people/employees/details:
 *   Personal Information, Contact Information, Emergency Contact,
 *   Employment, Salary, Allowances, Bank Details, Education,
 *   Experience, Document Expiry.
 *
 * One row per employee; multi-row relations (education, experience,
 * allowances, documents, bank accounts) are flattened with " | "
 * between records so Excel stays single-sheet and HR can sort/filter
 * the result like a normal spreadsheet.
 */
class SelectedEmployeesExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $ids;

    public function __construct(array $ids)
    {
        $this->ids = $ids;
    }

    public function collection()
    {
        return Employee::whereIn('id', $this->ids)
            ->where('resort_id', auth()->user()->resort_id)
            ->with([
                'resortAdmin',
                'position',
                'department',
                'division',
                'section',
                'reportingToAdmin',
                'education',
                'experiance',
                'document',
                'allowance.allowanceName',
            ])
            ->get()
            ->map(function ($emp) {
                $admin = $emp->resortAdmin;

                // Permanent address comes from ResortAdmin (the legal/user
                // record); present_address is on Employee. Match the
                // detail-page wording so the export reads the same.
                $permanentAddress = collect([
                    $admin->address_line_1 ?? null,
                    $admin->address_line_2 ?? null,
                    $admin->city ?? null,
                    $admin->state ?? null,
                    $admin->country ?? null,
                    $admin->zip ?? null,
                ])->filter()->implode(', ');

                $allowanceStr = $emp->allowance->map(function ($a) {
                    $name = $a->allowanceName->cost_name ?? 'Allowance';
                    return $name . ': ' . number_format((float) $a->amount, 2)
                        . ' ' . ($a->amount_unit ?? '');
                })->implode(' | ');

                $educationStr = $emp->education->map(function ($e) {
                    return collect([
                        $e->education_level,
                        $e->degree,
                        $e->field_of_study,
                        $e->institution_name,
                        $e->location,
                        $e->attendance_period,
                    ])->filter()->implode(' / ');
                })->implode(' | ');

                $experienceStr = $emp->experiance->map(function ($x) {
                    return collect([
                        $x->job_title,
                        $x->company_name,
                        $x->employment_type,
                        $x->duration,
                        $x->location,
                        $x->reference_name,
                        $x->reference_contact,
                    ])->filter()->implode(' / ');
                })->implode(' | ');

                $documentsStr = $emp->document->map(function ($d) {
                    return collect([
                        $d->document_category,
                        $d->document_title,
                    ])->filter()->implode(': ');
                })->implode(' | ');

                // Bank fields live on Employee itself (legacy single-bank
                // schema). EmployeeBankDetails was added later for
                // multi-account; export both shapes so we don't lose
                // either layout.
                $bankLine = collect([
                    $emp->bank_name,
                    $emp->bank_branch,
                    $emp->account_holder_name,
                    $emp->account_no,
                    $emp->IBAN,
                    $emp->IFSC_BIC,
                ])->filter()->implode(' / ');

                $reportingTo = '';
                if ($emp->reportingToAdmin) {
                    $reportingTo = trim(($emp->reportingToAdmin->first_name ?? '')
                        . ' ' . ($emp->reportingToAdmin->last_name ?? ''));
                }

                return [
                    // --- Identity ---
                    'Employee ID'         => $emp->Emp_id,
                    'Title'               => $emp->title,
                    'First Name'          => $admin->first_name ?? '',
                    'Middle Name'         => $admin->middle_name ?? '',
                    'Last Name'           => $admin->last_name ?? '',
                    'Full Name'           => $admin->full_name ?? '',
                    'Gender'              => $admin->gender ?? '',

                    // --- Personal ---
                    'Date of Birth'       => $emp->dob ? Carbon::parse($emp->dob)->format('d M Y') : '',
                    'Marital Status'      => $emp->marital_status,
                    'Nationality'         => $emp->nationality,
                    'Religion'            => $emp->religion,
                    'Blood Group'         => $emp->blood_group,
                    'Passport Number'     => $emp->passport_number,
                    'NID'                 => $emp->nid,

                    // --- Contact ---
                    'Mobile Number'       => $admin->personal_phone ?? '',
                    'Email Address'       => $admin->email ?? '',
                    'Permanent Address'   => $permanentAddress,
                    'Present Address'     => $emp->present_address,

                    // --- Emergency contact ---
                    'Emergency Name'      => trim(($emp->emg_cont_first_name ?? '') . ' ' . ($emp->emg_cont_last_name ?? '')),
                    'Emergency Number'    => $emp->emg_cont_no,
                    'Emergency Relation'  => $emp->emg_cont_relationship,
                    'Emergency Email'     => $emp->emg_cont_email,
                    'Emergency Address'   => $emp->emg_cont_current_address,

                    // --- Employment ---
                    'Division'            => $emp->division->division_title ?? $emp->division->name ?? '',
                    'Department'          => $emp->department->name ?? '',
                    'Section'             => $emp->section->section_title ?? $emp->section->name ?? '',
                    'Position'            => $emp->position->position_title ?? '',
                    'Joining Date'        => $emp->joining_date ? Carbon::parse($emp->joining_date)->format('d M Y') : '',
                    'Employment Status'   => $emp->status,
                    'Employment Type'     => $emp->employment_type,
                    'Contract Type'       => $emp->contract_type,
                    'TIN'                 => $emp->tin,
                    'Reporting To'        => $reportingTo,
                    'Work Location'       => $emp->work_location,
                    'Leave Destination'   => $emp->leave_destination,

                    // --- Salary ---
                    'Basic Salary'        => $emp->basic_salary,
                    'Basic Salary Currency' => $emp->basic_salary_currency ?: 'USD',
                    'Payment Mode'        => $emp->payment_mode,
                    'Incremented Date'    => $emp->incremented_date ? Carbon::parse($emp->incremented_date)->format('d M Y') : '',
                    'Last Increment Amount' => $emp->last_increment_salary_amount,
                    'Last Increment Type' => $emp->last_salary_increment_type,
                    'Allowances'          => $allowanceStr,

                    // --- Bank ---
                    'Bank Details'        => $bankLine,

                    // --- Education / Experience / Documents ---
                    'Education'           => $educationStr,
                    'Experience'          => $experienceStr,
                    'Documents'           => $documentsStr,

                    // --- Probation / contract milestones ---
                    'Probation End Date'  => $emp->probation_end_date ? Carbon::parse($emp->probation_end_date)->format('d M Y') : '',
                    'Probation Status'    => $emp->probation_status,
                    'Confirmation Date'   => $emp->confirmation_date ? Carbon::parse($emp->confirmation_date)->format('d M Y') : '',
                    'Contract End Date'   => $emp->contract_end_date ? Carbon::parse($emp->contract_end_date)->format('d M Y') : '',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Employee ID', 'Title', 'First Name', 'Middle Name', 'Last Name', 'Full Name', 'Gender',
            'Date of Birth', 'Marital Status', 'Nationality', 'Religion', 'Blood Group', 'Passport Number', 'NID',
            'Mobile Number', 'Email Address', 'Permanent Address', 'Present Address',
            'Emergency Name', 'Emergency Number', 'Emergency Relation', 'Emergency Email', 'Emergency Address',
            'Division', 'Department', 'Section', 'Position', 'Joining Date', 'Employment Status',
            'Employment Type', 'Contract Type', 'TIN', 'Reporting To', 'Work Location', 'Leave Destination',
            'Basic Salary', 'Basic Salary Currency', 'Payment Mode', 'Incremented Date',
            'Last Increment Amount', 'Last Increment Type', 'Allowances',
            'Bank Details',
            'Education', 'Experience', 'Documents',
            'Probation End Date', 'Probation Status', 'Confirmation Date', 'Contract End Date',
        ];
    }
}
