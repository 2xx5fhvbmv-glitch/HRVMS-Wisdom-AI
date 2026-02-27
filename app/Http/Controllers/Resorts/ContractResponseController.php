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
            ->selectRaw('t1.id as position_id, t2.id as dept_id, vacancies.reporting_to')
            ->first();

        // Generate password
        $plainPassword = Common::generateUniquePassword(8);

        // Create ResortAdmin record
        $resortAdmin = ResortAdmin::create([
            'email' => $applicant->email,
            'first_name' => $applicant->first_name,
            'last_name' => $applicant->last_name,
            'password' => Hash::make($plainPassword),
            'resort_id' => $contract->resort_id,
            'status' => 'Active',
        ]);

        // Create Employee record
        $employee = Employee::create([
            'Admin_Parent_id' => $resortAdmin->id,
            'resort_id' => $contract->resort_id,
            'first_name' => $applicant->first_name,
            'last_name' => $applicant->last_name,
            'email' => $applicant->email,
            'phone' => $applicant->mobile_number,
            'country_phone_code' => $applicant->country_phone_code,
            'nationality' => $applicant->country,
            'passport_no' => $applicant->passport_no,
            'passport_expiry_date' => $applicant->passport_expiry_date,
            'dob' => $applicant->dob,
            'gender' => $applicant->gender,
            'marital_status' => $applicant->marital_status,
            'address_line_one' => $applicant->address_line_one,
            'address_line_two' => $applicant->address_line_two,
            'country' => $applicant->country,
            'state' => $applicant->state,
            'city' => $applicant->city,
            'pin_code' => $applicant->pin_code,
            'Dept_id' => $vacancy->dept_id ?? null,
            'Position_id' => $vacancy->position_id ?? null,
            'joining_date' => $applicant->Joining_availability,
            'reporting_to' => $vacancy->reporting_to ?? null,
            'status' => 'Active',
        ]);

        // Update ResortAdmin with employee link
        $resortAdmin->update(['employee_id' => $employee->id]);

        // Copy education records
        $educations = DB::table('education_applicant_form')
            ->where('applicant_form_id', $applicant->id)
            ->get();

        foreach ($educations as $edu) {
            DB::table('employee_education')->insert([
                'employee_id' => $employee->id,
                'degree' => $edu->degree ?? null,
                'institution' => $edu->institution ?? null,
                'field_of_study' => $edu->field_of_study ?? null,
                'start_date' => $edu->start_date ?? null,
                'end_date' => $edu->end_date ?? null,
                'pass_out_year' => $edu->pass_out_year ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Copy experience records
        $experiences = DB::table('work_experience_applicant_form')
            ->where('applicant_form_id', $applicant->id)
            ->get();

        foreach ($experiences as $exp) {
            DB::table('employee_experiance')->insert([
                'employee_id' => $employee->id,
                'job_title' => $exp->job_title ?? null,
                'company_name' => $exp->company_name ?? null,
                'work_start_date' => $exp->work_start_date ?? null,
                'work_end_date' => $exp->work_end_date ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Copy language records
        $languages = DB::table('applicant_languages')
            ->where('applicant_form_id', $applicant->id)
            ->get();

        foreach ($languages as $lang) {
            DB::table('employee_language')->insert([
                'employee_id' => $employee->id,
                'language' => $lang->language ?? null,
                'proficiency' => $lang->proficiency ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Check if foreigner — create visa entry
        $resortCountry = $resort->country ?? null;
        if ($resortCountry && $applicant->country != $resortCountry) {
            DB::table('visa_employee_expiry_data')->insert([
                'employee_id' => $employee->id,
                'resort_id' => $contract->resort_id,
                'passport_no' => $applicant->passport_no,
                'passport_expiry_date' => $applicant->passport_expiry_date,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Send welcome email with credentials
        $this->sendWelcomeEmail($applicant, $resort, $plainPassword);
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
