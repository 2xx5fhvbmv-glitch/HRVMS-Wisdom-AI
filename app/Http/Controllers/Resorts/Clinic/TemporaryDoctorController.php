<?php

namespace App\Http\Controllers\Resorts\Clinic;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\TemporaryClinicDoctor;
use App\Helpers\Common;
use Carbon\Carbon;

/**
 * HR-side management of third-party/agency clinic doctor accounts — see
 * database/migrations/2026_08_13_000001_create_temporary_clinic_doctors_table.php
 * and app/Models/TemporaryClinicDoctor.php for why this is a standalone
 * account type, never an Employee row.
 */
class TemporaryDoctorController extends Controller
{
    public $resort;

    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
    }

    private function ensureAccess()
    {
        if (!Common::hasFullDataAccess()) {
            abort(403, 'Unauthorized access');
        }
    }

    public function index()
    {
        $this->ensureAccess();

        $page_title = 'Temporary Clinic Doctors';
        $doctors = TemporaryClinicDoctor::where('resort_id', $this->resort->resort_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('resorts.clinic.temporary-doctors.index', compact('page_title', 'doctors'));
    }

    public function store(Request $request)
    {
        $this->ensureAccess();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:temporary_clinic_doctors,email',
            'contact_no' => 'nullable|string|max:20',
            'agency_name' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date|after:today',
            'can_view_appointments' => 'nullable|boolean',
            'can_manage_treatment' => 'nullable|boolean',
            'can_view_medical_history' => 'nullable|boolean',
            'can_issue_medical_certificate' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $plainPassword = Common::generateUniquePassword(10);

        $doctor = TemporaryClinicDoctor::create([
            'resort_id' => $this->resort->resort_id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($plainPassword),
            'contact_no' => $request->contact_no,
            'agency_name' => $request->agency_name,
            'expires_at' => $request->expires_at,
            'can_view_appointments' => $request->boolean('can_view_appointments'),
            'can_manage_treatment' => $request->boolean('can_manage_treatment'),
            'can_view_medical_history' => $request->boolean('can_view_medical_history'),
            'can_issue_medical_certificate' => $request->boolean('can_issue_medical_certificate'),
            'status' => 'Active',
            'created_by' => $this->resort->id,
        ]);

        $doctor->sendCredentialsEmail($plainPassword);

        return response()->json([
            'success' => true,
            'message' => 'Temporary doctor account created. Login credentials have been emailed.',
            'redirect_url' => route('resort.clinic.temporary-doctors.index'),
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->ensureAccess();

        $doctor = TemporaryClinicDoctor::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'contact_no' => 'nullable|string|max:20',
            'agency_name' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date',
            'can_view_appointments' => 'nullable|boolean',
            'can_manage_treatment' => 'nullable|boolean',
            'can_view_medical_history' => 'nullable|boolean',
            'can_issue_medical_certificate' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $doctor->update([
            'name' => $request->name,
            'contact_no' => $request->contact_no,
            'agency_name' => $request->agency_name,
            'expires_at' => $request->expires_at,
            'can_view_appointments' => $request->boolean('can_view_appointments'),
            'can_manage_treatment' => $request->boolean('can_manage_treatment'),
            'can_view_medical_history' => $request->boolean('can_view_medical_history'),
            'can_issue_medical_certificate' => $request->boolean('can_issue_medical_certificate'),
        ]);

        return response()->json(['success' => true, 'message' => 'Account updated.']);
    }

    public function resetPassword($id)
    {
        $this->ensureAccess();

        $doctor = TemporaryClinicDoctor::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $plainPassword = Common::generateUniquePassword(10);
        $doctor->password = Hash::make($plainPassword);
        $doctor->save();
        $doctor->sendCredentialsEmail($plainPassword);

        // A password reset should also kill any session the old password
        // was still valid for — otherwise a leaked/expired-contract login
        // stays usable on whatever device already holds a token.
        $doctor->tokens->each(function ($token) {
            $token->revoke();
        });

        return response()->json(['success' => true, 'message' => 'New password generated and emailed.']);
    }

    public function revokeSessions($id)
    {
        $this->ensureAccess();

        $doctor = TemporaryClinicDoctor::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $count = $doctor->tokens->count();
        $doctor->tokens->each(function ($token) {
            $token->revoke();
        });

        return response()->json(['success' => true, 'message' => "Signed out of {$count} active session(s)."]);
    }

    public function toggleStatus($id)
    {
        $this->ensureAccess();

        $doctor = TemporaryClinicDoctor::where('resort_id', $this->resort->resort_id)->find($id);
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $doctor->status = $doctor->status === 'Active' ? 'Inactive' : 'Active';
        $doctor->save();

        // Deactivating should immediately cut off any live session, not
        // just block the next login.
        if ($doctor->status === 'Inactive') {
            $doctor->tokens->each(function ($token) {
                $token->revoke();
            });
        }

        return response()->json(['success' => true, 'message' => 'Status updated.', 'status' => $doctor->status]);
    }
}
