<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Models\ApplicantOfferContract;
use App\Models\Applicant_form_data;
use App\Models\ApplicantWiseStatus;
use App\Models\Vacancies;
use App\Models\Resort;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OfferLetterResponseController extends Controller
{
    public function show($token)
    {
        $offer = ApplicantOfferContract::where('token', $token)
            ->where('type', 'offer_letter')
            ->first();

        if (!$offer) {
            return view('resorts.offer_letter.show', [
                'error' => 'Invalid offer letter link.',
                'offer' => null,
            ]);
        }

        $applicant = Applicant_form_data::find($offer->applicant_id);
        $resort = Resort::find($offer->resort_id);

        $vacancy = Vacancies::join('resort_positions as t1', 't1.id', '=', 'vacancies.position')
            ->join('resort_departments as t2', 't2.id', '=', 't1.dept_id')
            ->where('vacancies.id', $applicant->Parent_v_id ?? 0)
            ->selectRaw('t1.position_title as position, t2.name as department')
            ->first();

        return view('resorts.offer_letter.show', [
            'error' => null,
            'offer' => $offer,
            'applicant' => $applicant,
            'resort' => $resort,
            'vacancy' => $vacancy,
            'token' => $token,
        ]);
    }

    public function accept($token)
    {
        $offer = ApplicantOfferContract::where('token', $token)
            ->where('type', 'offer_letter')
            ->first();

        if (!$offer) {
            return redirect()->route('resort.offer.letter.show', $token)
                ->with('error', 'Invalid offer letter link.');
        }

        if ($offer->status === 'Accepted') {
            return redirect()->route('resort.offer.letter.show', $token)
                ->with('info', 'You have already accepted this offer letter.');
        }

        if ($offer->status === 'Rejected') {
            return redirect()->route('resort.offer.letter.show', $token)
                ->with('error', 'This offer letter has already been rejected.');
        }

        $offer->update([
            'status' => 'Accepted',
            'responded_at' => Carbon::now(),
        ]);

        // Update applicant status
        ApplicantWiseStatus::where('id', $offer->applicant_status_id)
            ->update(['status' => 'Offer Letter Accepted']);

        return redirect()->route('resort.offer.letter.show', $token)
            ->with('success', 'Offer letter accepted successfully! You will receive your contract shortly.');
    }

    public function reject(Request $request, $token)
    {
        $offer = ApplicantOfferContract::where('token', $token)
            ->where('type', 'offer_letter')
            ->first();

        if (!$offer) {
            return redirect()->route('resort.offer.letter.show', $token)
                ->with('error', 'Invalid offer letter link.');
        }

        if ($offer->status === 'Rejected') {
            return redirect()->route('resort.offer.letter.show', $token)
                ->with('info', 'You have already rejected this offer letter.');
        }

        if ($offer->status === 'Accepted') {
            return redirect()->route('resort.offer.letter.show', $token)
                ->with('error', 'This offer letter has already been accepted.');
        }

        $offer->update([
            'status' => 'Rejected',
            'rejection_reason' => $request->rejection_reason,
            'responded_at' => Carbon::now(),
        ]);

        ApplicantWiseStatus::where('id', $offer->applicant_status_id)
            ->update(['status' => 'Offer Letter Rejected']);

        return redirect()->route('resort.offer.letter.show', $token)
            ->with('success', 'Offer letter has been declined.');
    }
}
