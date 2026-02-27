<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Models\Applicant_form_data;
use App\Models\Resort;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ConsentResponseController extends Controller
{
    public function show($token)
    {
        $applicant = Applicant_form_data::where('consent_token', $token)->first();

        if (!$applicant) {
            return view('resorts.consent.show', [
                'error' => 'Invalid consent link.',
                'applicant' => null,
            ]);
        }

        $resort = Resort::find($applicant->resort_id);

        return view('resorts.consent.show', [
            'error' => null,
            'applicant' => $applicant,
            'resort' => $resort,
            'token' => $token,
        ]);
    }

    public function approve($token)
    {
        $applicant = Applicant_form_data::where('consent_token', $token)->first();

        if (!$applicant) {
            return redirect()->route('resort.consent.show', $token)
                ->with('error', 'Invalid consent link.');
        }

        if ($applicant->consent_status === 'approved') {
            return redirect()->route('resort.consent.show', $token)
                ->with('info', 'You have already approved this consent request.');
        }

        $applicant->update([
            'consent_status' => 'approved',
            'consent_responded_at' => Carbon::now(),
        ]);

        return redirect()->route('resort.consent.show', $token)
            ->with('success', 'Thank you! Your consent has been recorded. We will retain your data until ' . Carbon::parse($applicant->consent_expiry_date)->format('d M Y') . '.');
    }

    public function reject(Request $request, $token)
    {
        $applicant = Applicant_form_data::where('consent_token', $token)->first();

        if (!$applicant) {
            return redirect()->route('resort.consent.show', $token)
                ->with('error', 'Invalid consent link.');
        }

        if ($applicant->consent_status === 'rejected') {
            return redirect()->route('resort.consent.show', $token)
                ->with('info', 'You have already rejected this consent request.');
        }

        $applicant->update([
            'consent_status' => 'rejected',
            'consent_responded_at' => Carbon::now(),
        ]);

        return redirect()->route('resort.consent.show', $token)
            ->with('success', 'Your data will be removed as per your request.');
    }
}
