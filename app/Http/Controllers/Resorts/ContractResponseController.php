<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Models\ApplicantOfferContract;
use App\Models\Applicant_form_data;
use App\Models\ApplicantWiseStatus;
use App\Models\Vacancies;
use App\Models\Resort;
use App\Models\ResortAdmin;
use App\Models\Employee;
use App\Helpers\Common;
use App\Jobs\TaEmailSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ContractResponseController extends Controller
{
    public function show($token)
    {
        $contract = ApplicantOfferContract::where('token', $token)
            ->where('type', 'contract')
            ->first();

        if (!$contract) {
            return view('resorts.contract.show', [
                'error' => 'Invalid contract link.',
                'contract' => null,
            ]);
        }

        $applicant = Applicant_form_data::find($contract->applicant_id);
        $resort = Resort::find($contract->resort_id);

        $vacancy = Vacancies::join('resort_positions as t1', 't1.id', '=', 'vacancies.position')
            ->join('resort_departments as t2', 't2.id', '=', 't1.dept_id')
            ->where('vacancies.id', $applicant->Parent_v_id ?? 0)
            ->selectRaw('t1.position_title as position, t2.name as department, t1.id as position_id, t2.id as dept_id, vacancies.reporting_to')
            ->first();

        return view('resorts.contract.show', [
            'error' => null,
            'contract' => $contract,
            'applicant' => $applicant,
            'resort' => $resort,
            'vacancy' => $vacancy,
            'token' => $token,
        ]);
    }

    public function accept($token)
    {
        $contract = ApplicantOfferContract::where('token', $token)
            ->where('type', 'contract')
            ->first();

        if (!$contract) {
            return redirect()->route('resort.contract.show', $token)
                ->with('error', 'Invalid contract link.');
        }

        if ($contract->status === 'Accepted') {
            return redirect()->route('resort.contract.show', $token)
                ->with('info', 'You have already accepted this contract.');
        }

        if ($contract->status === 'Rejected') {
            return redirect()->route('resort.contract.show', $token)
                ->with('error', 'This contract has already been rejected.');
        }

        DB::beginTransaction();
        try {
            $contract->update([
                'status' => 'Accepted',
                'responded_at' => Carbon::now(),
            ]);

            ApplicantWiseStatus::where('id', $contract->applicant_status_id)
                ->update(['status' => 'Contract Accepted']);

            // Auto-create employee
            $this->createEmployeeFromApplicant($contract);

            // Send contract email with download link
            $this->sendContractEmail($contract);

            DB::commit();

            return redirect()->route('resort.contract.show', $token)
                ->with('success', 'Contract accepted successfully! Welcome aboard! You will receive your login credentials shortly.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("Contract accept error: " . $e->getMessage() . " Line: " . $e->getLine());
            return redirect()->route('resort.contract.show', $token)
                ->with('error', 'Something went wrong. Please try again or contact HR.');
        }
    }

    public function reject(Request $request, $token)
    {
        $contract = ApplicantOfferContract::where('token', $token)
            ->where('type', 'contract')
            ->first();

        if (!$contract) {
            return redirect()->route('resort.contract.show', $token)
                ->with('error', 'Invalid contract link.');
        }

        if ($contract->status === 'Rejected') {
            return redirect()->route('resort.contract.show', $token)
                ->with('info', 'You have already rejected this contract.');
        }

        if ($contract->status === 'Accepted') {
            return redirect()->route('resort.contract.show', $token)
                ->with('error', 'This contract has already been accepted.');
        }

        $contract->update([
            'status' => 'Rejected',
            'rejection_reason' => $request->rejection_reason,
            'responded_at' => Carbon::now(),
        ]);

        ApplicantWiseStatus::where('id', $contract->applicant_status_id)
            ->update(['status' => 'Contract Rejected']);

        return redirect()->route('resort.contract.show', $token)
            ->with('success', 'Contract has been declined.');
    }

    private function createEmployeeFromApplicant($contract)
    {
        $applicant = Applicant_form_data::find($contract->applicant_id);
        $resort = Resort::find($contract->resort_id);

        if (!$applicant || !$resort) return;

        $vacancy = Vacancies::join('resort_positions as t1', 't1.id', '=', 'vacancies.position')
            ->leftJoin('resort_departments as t2', 't2.id', '=', 't1.dept_id')
            ->where('vacancies.id', $applicant->Parent_v_id)
            ->selectRaw('t1.id as position_id, t1.Rank as position_rank, t2.id as dept_id, t2.division_id, vacancies.reporting_to')
            ->first();

        // Generate Employee ID (DR-{next number})
        $maxEmpNum = DB::table('employees')
            ->where('resort_id', $contract->resort_id)
            ->where('Emp_id', 'like', 'DR-%')
            ->selectRaw("MAX(CAST(SUBSTRING(Emp_id, 4) AS UNSIGNED)) as max_num")
            ->value('max_num');
        $empId = 'DR-' . (($maxEmpNum ?? 0) + 1);

        // Look up country name for nationality
        $countryName = DB::table('countries')->where('id', $applicant->country)->value('name');

        // Validate joining date
        $joiningDate = null;
        if ($applicant->Joining_availability) {
            try {
                $joiningDate = Carbon::parse($applicant->Joining_availability)->format('Y-m-d');
                if ($joiningDate === '0000-00-00' || $joiningDate === '-0001-11-30') {
                    $joiningDate = null;
                }
            } catch (\Exception $e) {
                $joiningDate = null;
            }
        }

        // Get basic salary and allowances from budget/existing employees
        $vacantBudget = DB::table('resort_vacant_budget_costs')
            ->where('position_id', $vacancy->position_id ?? 0)
            ->where('resort_id', $contract->resort_id)
            ->first();
        $basicSalary = ($vacantBudget && $vacantBudget->basic_salary > 0) ? $vacantBudget->basic_salary : null;
        $salaryCurrency = 'USD';

        // If vacant budget has no salary, check existing employees in same position
        $referenceEmployee = null;
        if (!$basicSalary) {
            $referenceEmployee = Employee::where('Position_id', $vacancy->position_id ?? 0)
                ->where('resort_id', $contract->resort_id)
                ->where('status', 'Active')
                ->where('basic_salary', '>', 0)
                ->first();
            if ($referenceEmployee) {
                $basicSalary = $referenceEmployee->basic_salary;
                $salaryCurrency = $referenceEmployee->basic_salary_currency ?? 'USD';
            }
        }

        // Generate password
        $plainPassword = Common::generateUniquePassword(8);

        // Create ResortAdmin record (stores personal info: name, email, phone, address)
        $resortAdmin = ResortAdmin::create([
            'resort_id' => $contract->resort_id,
            'first_name' => $applicant->first_name,
            'last_name' => $applicant->last_name,
            'email' => $applicant->email,
            'personal_phone' => $applicant->mobile_number,
            'gender' => $applicant->gender,
            'password' => Hash::make($plainPassword),
            'address_line_1' => $applicant->address_line_one,
            'address_line_2' => $applicant->address_line_two,
            'city' => $applicant->city,
            'state' => $applicant->state,
            'country' => $countryName ?? $applicant->country,
            'type' => 'sub',
            'is_employee' => 1,
            'is_master_admin' => 0,
            'status' => 'Active',
        ]);

        // Create Employee record (stores job info: department, position, dates)
        $employee = Employee::create([
            'Admin_Parent_id' => $resortAdmin->id,
            'resort_id' => $contract->resort_id,
            'Emp_id' => $empId,
            'title' => $applicant->gender == 'male' ? 'Mr.' : 'Ms.',
            'is_employee' => 1,
            'Dept_id' => $vacancy->dept_id ?? null,
            'Position_id' => $vacancy->position_id ?? null,
            'division_id' => $vacancy->division_id ?? null,
            'rank' => $vacancy->position_rank ?? null,
            'reporting_to' => $vacancy->reporting_to ?? null,
            'benefit_grid_level' => $vacancy->position_rank ?? null,
            'nationality' => $countryName ?? $applicant->country,
            'dob' => $applicant->dob,
            'marital_status' => $applicant->marital_status,
            'passport_number' => $applicant->passport_no,
            'joining_date' => $joiningDate,
            'basic_salary' => $basicSalary,
            'basic_salary_currency' => $salaryCurrency,
            'present_address' => trim(($applicant->address_line_one ?? '') . ', ' . ($applicant->address_line_two ?? '') . ', ' . ($applicant->city ?? '') . ', ' . ($applicant->state ?? '') . ', ' . ($applicant->pin_code ?? '') . ', ' . ($countryName ?? ''), ', '),
            'status' => 'Active',
            'created_by' => $contract->sent_by,
            'modified_by' => $contract->sent_by,
        ]);

        // Copy education records
        $educations = DB::table('education_applicant_form')
            ->where('applicant_form_id', $applicant->id)
            ->get();

        foreach ($educations as $edu) {
            DB::table('employees_education')->insert([
                'employee_id' => $employee->id,
                'education_level' => $edu->educational_level ?? null,
                'institution_name' => $edu->institute_name ?? null,
                'location' => trim(($edu->city_educational ?? '') . ', ' . ($edu->country_educational ?? ''), ', '),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Copy experience records
        $experiences = DB::table('work_experience_applicant_form')
            ->where('applicant_form_id', $applicant->id)
            ->get();

        foreach ($experiences as $exp) {
            DB::table('employees_experiance')->insert([
                'employee_id' => $employee->id,
                'job_title' => $exp->job_title ?? null,
                'company_name' => $exp->employer_name ?? null,
                'duration' => $exp->total_work_exp ?? null,
                'location' => trim(($exp->work_city ?? '') . ', ' . ($exp->work_country_name ?? ''), ', '),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Copy language records
        $languages = DB::table('applicant_languages')
            ->where('applicant_form_id', $applicant->id)
            ->get();

        foreach ($languages as $lang) {
            DB::table('employees_language')->insert([
                'employee_id' => $employee->id,
                'language' => $lang->language ?? null,
                'proficiency_level' => $lang->level ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Transfer budget data from vacant position to employee
        $budgetTransferred = false;
        if ($vacantBudget) {
            $allVacantConfigs = DB::table('resort_vacant_budget_cost_configurations')
                ->where('vacant_budget_cost_id', $vacantBudget->id)
                ->get();

            if ($allVacantConfigs->isNotEmpty()) {
                $budgetTransferred = true;
                // Copy all vacant budget configs to employee budget configs
                foreach ($allVacantConfigs as $config) {
                    DB::table('resort_employee_budget_cost_configurations')->insert([
                        'employee_id' => $employee->id,
                        'resort_budget_cost_id' => $config->resort_budget_cost_id,
                        'value' => $config->value,
                        'currency' => $config->currency ?? 'USD',
                        'hours' => $config->hours ?? 0,
                        'department_id' => $config->department_id,
                        'position_id' => $config->position_id,
                        'resort_id' => $config->resort_id,
                        'year' => $config->year,
                        'month' => $config->month,
                        'basic_salary' => $basicSalary,
                        'current_salary' => $vacantBudget->current_salary ?? 0,
                        'created_by' => $contract->sent_by,
                        'modified_by' => $contract->sent_by,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Create employee allowance records from current month's budget configs
                $currentMonthConfigs = $allVacantConfigs->where('month', Carbon::now()->month);
                foreach ($currentMonthConfigs as $config) {
                    DB::table('employees_allowance')->insert([
                        'employee_id' => $employee->id,
                        'allowance_id' => $config->resort_budget_cost_id,
                        'amount' => $config->value,
                        'amount_unit' => $config->currency ?? 'USD',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // If no budget configs found, copy from existing employee in same position
        if (!$budgetTransferred && $referenceEmployee) {
            // Copy budget cost configurations
            $refConfigs = DB::table('resort_employee_budget_cost_configurations')
                ->where('employee_id', $referenceEmployee->id)
                ->where('year', Carbon::now()->year)
                ->get();

            foreach ($refConfigs as $config) {
                DB::table('resort_employee_budget_cost_configurations')->insert([
                    'employee_id' => $employee->id,
                    'resort_budget_cost_id' => $config->resort_budget_cost_id,
                    'value' => $config->value,
                    'currency' => $config->currency ?? 'USD',
                    'hours' => $config->hours ?? 0,
                    'department_id' => $config->department_id,
                    'position_id' => $config->position_id,
                    'resort_id' => $config->resort_id,
                    'year' => $config->year,
                    'month' => $config->month,
                    'basic_salary' => $basicSalary,
                    'current_salary' => $basicSalary,
                    'created_by' => $contract->sent_by,
                    'modified_by' => $contract->sent_by,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Copy allowances from reference employee
            $refAllowances = DB::table('employees_allowance')
                ->where('employee_id', $referenceEmployee->id)
                ->get();

            foreach ($refAllowances as $allowance) {
                DB::table('employees_allowance')->insert([
                    'employee_id' => $employee->id,
                    'allowance_id' => $allowance->allowance_id,
                    'amount' => $allowance->amount,
                    'amount_unit' => $allowance->amount_unit,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Send welcome email with credentials
        $this->sendWelcomeEmail($applicant, $resort, $plainPassword);
    }

    private function sendContractEmail($contract)
    {
        try {
            $applicant = Applicant_form_data::find($contract->applicant_id);
            if (!$applicant || !$applicant->email) return;

            $resort = Resort::find($contract->resort_id);
            $candidateName = ucfirst($applicant->first_name) . ' ' . ucfirst($applicant->last_name);
            $resortName = $resort->resort_name ?? '';
            $downloadLink = asset('storage/' . $contract->file_path);

            $vacancy = Vacancies::join('resort_positions as t1', 't1.id', '=', 'vacancies.position')
                ->where('vacancies.id', $applicant->Parent_v_id ?? 0)
                ->value('t1.position_title');

            $subject = "Your Employment Contract - {$resortName}";
            $body = "
                <p>Dear {$candidateName},</p>
                <p>Thank you for accepting the contract. Please find your employment contract below.</p>
                <table style='width:100%;border-collapse:collapse;margin:16px 0;'>
                    <tr><td style='padding:8px;border:1px solid #ddd;font-weight:600;'>Position</td><td style='padding:8px;border:1px solid #ddd;'>" . ($vacancy ?? '-') . "</td></tr>
                    <tr><td style='padding:8px;border:1px solid #ddd;font-weight:600;'>Resort</td><td style='padding:8px;border:1px solid #ddd;'>{$resortName}</td></tr>
                </table>
                <p><a href='{$downloadLink}' target='_blank' style='display:inline-block;padding:12px 24px;background:#004552;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;'>Download Contract</a></p>
                <p>&nbsp;</p>
                <p>Regards,</p>
                <p>HR Team</p>
                <p>{$resortName}</p>
            ";

            TaEmailSent::dispatch($applicant->email, $subject, ['mainbody' => $body]);
        } catch (\Exception $e) {
            \Log::warning("Failed to send contract email: " . $e->getMessage());
        }
    }

    private function sendWelcomeEmail($applicant, $resort, $password)
    {
        try {
            $subject = "Welcome to " . ($resort->resort_name ?? 'our organization') . " - Your Login Credentials";
            $loginUrl = route('resort.loginindex');
            $body = "
                <p>Dear " . ucfirst($applicant->first_name) . " " . ucfirst($applicant->last_name) . ",</p>
                <p>Congratulations! Your contract has been accepted and your employee account has been created.</p>
                <p>Here are your login credentials:</p>
                <table style='width:100%;border-collapse:collapse;margin:16px 0;'>
                    <tr><td style='padding:8px;border:1px solid #ddd;font-weight:600;'>Email</td><td style='padding:8px;border:1px solid #ddd;'>{$applicant->email}</td></tr>
                    <tr><td style='padding:8px;border:1px solid #ddd;font-weight:600;'>Password</td><td style='padding:8px;border:1px solid #ddd;'>{$password}</td></tr>
                    <tr><td style='padding:8px;border:1px solid #ddd;font-weight:600;'>Login URL</td><td style='padding:8px;border:1px solid #ddd;'><a href='{$loginUrl}'>{$loginUrl}</a></td></tr>
                </table>
                <p>Please change your password after your first login.</p>
                <p>Regards,<br>HR Team<br>" . ($resort->resort_name ?? '') . "</p>
            ";

            \App\Jobs\TaEmailSent::dispatch($applicant->email, $subject, ['mainbody' => $body]);
        } catch (\Exception $e) {
            \Log::warning("Welcome email failed: " . $e->getMessage());
        }
    }
}
