<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Models\Applicant_form_data;
use App\Models\Resort;
use Carbon\Carbon;

class AvailabilityResponseController extends Controller
{
    public function show($token)
    {
        $applicant = Applicant_form_data::where('availability_token', $token)->first();

        if (!$applicant) {
            return view('resorts.availability.show', [
                'error' => 'Invalid availability link.',
                'applicant' => null,
            ]);
        }

        $resort = Resort::find($applicant->resort_id);

        return view('resorts.availability.show', [
            'error' => null,
            'applicant' => $applicant,
            'resort' => $resort,
            'token' => $token,
        ]);
    }

    public function available($token)
    {
        $applicant = Applicant_form_data::where('availability_token', $token)->first();

        if (!$applicant) {
            return redirect()->route('resort.availability.show', $token)
                ->with('error', 'Invalid availability link.');
        }

        if ($applicant->availability_status === 'available') {
            return redirect()->route('resort.availability.show', $token)
                ->with('info', 'You have already confirmed your availability.');
        }

        $applicant->update([
            'availability_status' => 'available',
            'availability_responded_at' => Carbon::now(),
        ]);

        return redirect()->route('resort.availability.show', $token)
            ->with('success', 'Thank you! Your availability has been confirmed. Our HR team will be in touch soon.');
    }

    public function unavailable($token)
    {
        $applicant = Applicant_form_data::where('availability_token', $token)->first();

        if (!$applicant) {
            return redirect()->route('resort.availability.show', $token)
                ->with('error', 'Invalid availability link.');
        }

        if ($applicant->availability_status === 'unavailable') {
            return redirect()->route('resort.availability.show', $token)
                ->with('info', 'You have already responded to this request.');
        }

        $applicant->update([
            'availability_status' => 'unavailable',
            'availability_responded_at' => Carbon::now(),
        ]);

        return redirect()->route('resort.availability.show', $token)
            ->with('success', 'Thank you for letting us know. We will keep your profile for future opportunities.');
    }
}
