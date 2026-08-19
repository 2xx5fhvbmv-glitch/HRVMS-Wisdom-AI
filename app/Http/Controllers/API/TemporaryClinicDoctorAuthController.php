<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\TemporaryClinicDoctor;
use Validator;

/**
 * Mirrors LoginController::apiLogin()'s token-issuance pattern
 * ($model->createToken()->accessToken — no OAuth password-grant flow exists
 * in this app) for the separate temp-clinic-doctor identity.
 */
class TemporaryClinicDoctorAuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()], 422);
        }

        try {
            $doctor = TemporaryClinicDoctor::where('email', $request->email)->first();

            if (!$doctor || !Hash::check($request->password, $doctor->password)) {
                return response()->json(['success' => false, 'message' => 'Invalid email or password.'], 200);
            }

            if (!$doctor->isActive()) {
                return response()->json(['success' => false, 'message' => 'This account has been deactivated or has expired.'], 200);
            }

            $token = $doctor->createToken('TemporaryClinicDoctorToken')->accessToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
                'doctor' => [
                    'id' => $doctor->id,
                    'name' => $doctor->name,
                    'email' => $doctor->email,
                    'agency_name' => $doctor->agency_name,
                    'permissions' => [
                        'can_view_appointments' => $doctor->can_view_appointments,
                        'can_manage_treatment' => $doctor->can_manage_treatment,
                        'can_view_medical_history' => $doctor->can_view_medical_history,
                        'can_issue_medical_certificate' => $doctor->can_issue_medical_certificate,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile());
            \Log::emergency('Line: ' . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    // Doctor-facing "my profile" screen — the app gets this same shape from
    // the login response already, but has no way to re-fetch it (e.g. after
    // a silent token-based app reopen) without this.
    public function profile(Request $request)
    {
        $doctor = Auth::guard('temp-clinic-doctor')->user();
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile fetched successfully',
            'doctor' => [
                'id' => $doctor->id,
                'name' => $doctor->name,
                'email' => $doctor->email,
                'agency_name' => $doctor->agency_name,
                'contact_no' => $doctor->contact_no,
                'permissions' => [
                    'can_view_appointments' => $doctor->can_view_appointments,
                    'can_manage_treatment' => $doctor->can_manage_treatment,
                    'can_view_medical_history' => $doctor->can_view_medical_history,
                    'can_issue_medical_certificate' => $doctor->can_issue_medical_certificate,
                ],
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $doctor = Auth::guard('temp-clinic-doctor')->user();
        if (!$doctor) {
            return response()->json(['success' => false, 'message' => 'No authenticated user'], 401);
        }

        $token = $request->user('temp-clinic-doctor')->token();
        if ($token) {
            $token->revoke();
        }

        return response()->json(['success' => true, 'message' => 'Logged out successfully']);
    }
}
