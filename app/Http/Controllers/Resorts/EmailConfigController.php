<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use Validator;
use App\Models\ResortSmtpConfig;
use App\Helpers\Common;
use Illuminate\Support\Facades\Mail;

class EmailConfigController extends Controller
{
    public function index()
    {
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;
        $config = ResortSmtpConfig::where('resort_id', $resort_id)->first();

        return view('resorts.emailconfig.index', compact('config'));
    }

    public function store(Request $request)
    {
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;

        $validator = Validator::make($request->all(), [
            'host' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'encryption' => 'nullable|in:tls,ssl',
            'from_address' => 'required|email|max:255',
            'from_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $data = $validator->validated();

        // Password is never echoed back to the form, so a blank submission
        // means "keep the existing one" rather than "clear it".
        if (empty($data['password'])) {
            unset($data['password']);
        }

        ResortSmtpConfig::updateOrCreate(['resort_id' => $resort_id], $data);

        return response()->json(['success' => true, 'message' => 'Email configuration saved successfully.']);
    }

    public function test(Request $request)
    {
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;

        $validator = Validator::make($request->all(), [
            'test_email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $config = ResortSmtpConfig::where('resort_id', $resort_id)->first();
        if (!$config) {
            return response()->json(['success' => false, 'message' => 'Save your SMTP configuration before sending a test email.'], 422);
        }

        Common::applyResortSmtpConfig($resort_id);

        try {
            Mail::send('emails.commonEmail', ['mainbody' => 'This is a test email confirming your resort\'s SMTP configuration is working correctly.'], function ($message) use ($request, $config) {
                $message->to($request->test_email)->subject('SMTP Test Email — ' . $config->from_name);
            });

            return response()->json(['success' => true, 'message' => 'Test email sent successfully to ' . $request->test_email . '.']);
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to send test email: ' . $e->getMessage()], 500);
        }
    }
}
