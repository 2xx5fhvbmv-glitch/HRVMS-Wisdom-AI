<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\ResortAdmin;
use App\Models\Employee;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Validator;
use Illuminate\Support\Facades\Password;


class Logincontroller extends Controller
{
    use SendsPasswordResetEmails;

    public function apiLogin(Request $request)
    {
        
        $validator  = Validator::make($request->all(), [
            'emp_id'                                => 'required',
            'password'                              => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()], 422);
        }

        try {

            // Find the Employee by Emp_id
            $employee                               =   Employee::where('Emp_id', $request->emp_id)->first();

            if (!$employee) {
                return response()->json([
                    'success'                       =>  false,
                    'message'                       =>  'Invalid Employee ID or password. Please try again'
                ],200);
            }


            if ($employee->status == "Inactive") {
                return response()->json([
                    'success'                       =>  false,
                    'message'                       =>  'Account is deactivated'
                ],200);
            }

            // Find the ResortAdmin by Admin_Parent_id
            $resortAdmin                            =   ResortAdmin::where('id', $employee->Admin_Parent_id)->first();

            if (!$resortAdmin || !Hash::check($request->password, $resortAdmin->password)) {
                return response()->json([
                    'success'                       =>  false,
                    'message'                       =>  'Please enter a valid password'
                ],200);
            }

            if ($resortAdmin->status == "Inactive") {
                return response()->json([
                    'success'                       =>  false,
                    'message'                       =>  'Account is deactivated'
                ],200);
            }
            // Check if the user already has an active token
            // $existingToken                          =   $resortAdmin->tokens()->where('revoked', false)->first();

            // if ($existingToken) {
            //     return response()->json([
            //         'success'                       =>  false,
            //         'message'                       =>  'User is already logged in',
            //     ], 200);
            // }

            // Generate a new token
            $tokenResult                            =   $resortAdmin->createToken('ResortAdminToken');
            $token                                  =   $tokenResult->accessToken;

            return response()->json([
                'success'                           =>  true,
                'message'                           =>  'User Login Successfully',
                'token'                             =>  $token,
                'redirect_url'                      =>  route('resort.workforceplan.dashboard'),
            ]);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function apiLogout(Request $request)
    {
        try {
            // Get the currently authenticated user
            $resort_admin                           = Auth::guard('api')->user();

           
            // Check if the user is authenticated
            if (!$resort_admin) {
                return response()->json(['success'  => false, 'message' => 'No authenticated user'], 401);
            }

            // Get the token from the request
            $token = $request->user()->token();
            if (!$token) {
                return response()->json(['success'  => false, 'message' => 'No valid token found'], 400);
            }

            // Revoke the token
            $token->revoke(); // Passport-specific method to revoke the token


             $employee                               =   Employee::where('Admin_Parent_id', $resort_admin->id)->first();
             if($employee) {
                        // Clear the device token for the employee
                        $employee->device_token = NULL;
                        $employee->save();
             }
            return response()->json(['success'      => true, 'message' => 'User Logout Successfully'], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success'      => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    public function apiForgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()], 422);
        }

        try {
            $status = Password::broker('resort-admin')->sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                $admin = ResortAdmin::where('email', '=', $request->email)->first();
                $response['success']                    = true;
                $response['msg']                        = __('messages.passwordRequestSuccess', ['name' => 'Password Reset Request']);

                return response()->json($response);
            }else{
                $response['success']                    = false;
                $response['msg']                        =__($status);
                return response()->json($response);
            }
        } catch (\Exception $e) {
            $response['success']                    = false;
            $response['msg']                        = $e->getMessage();
            return response()->json($response);
        }
    }

    public function addDeviceToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'emp_id'                                =>  'required', // Employee ID
            'device_token'                          =>  'required', // Device token to be added
            
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()], 422);
        }

        try {
            // Find the employee by Emp_id
            $employee                               =   Employee::where('Emp_id', $request->emp_id)->first();

            if (!$employee) {
                return response()->json([
                    'success'                       =>  false,
                    'message'                       =>  'Employee not found',
                ], 404);
            }

            // Update the device_token
            $employee->device_token = $request->device_token;
            $employee->latitude = $request->latitude ?? null; // Set latitude if provided, otherwise null
            $employee->longitude = $request->longitude ?? null; // Set longitude if provided, otherwise null
            $employee->save();

            return response()->json([
                'success'                       =>  true,
                'message'                       => 'Device token updated successfully',
            ], 200);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
}
