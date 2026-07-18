<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Models\Applicant_form_data;
use App\Models\Resort;
use App\Models\Employee;
use App\Helpers\Common;
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
            // The Talent Pool listing's Availability badge reads
            // availability_status, not consent_status — reject() sets it to
            // 'consent_rejected' as a distinct signal, but approve() never
            // cleared it back, so an applicant who rejected then later
            // approved (a new consent link, or changing their mind) kept
            // showing "Consent Rejected" forever even though consent_status
            // was genuinely 'approved'. Reset to null ("Not Checked") so
            // the normal Check Availability flow can run again.
            'availability_status' => $applicant->availability_status === 'consent_rejected' ? null : $applicant->availability_status,
        ]);

        $this->notifyHrOfConsentResponse($applicant, 'approved');

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
            // availability_status was never touched by this method — a
            // rejected applicant kept showing whatever stale value was
            // there before (e.g. "Available to Reach"), which misleadingly
            // suggested they were still fine to contact/retain after they'd
            // explicitly withdrawn consent. Distinct value (not reusing
            // 'unavailable') since "temporarily can't reach them" and
            // "withdrew data-retention consent" are different things.
            'availability_status' => 'consent_rejected',
        ]);

        $this->notifyHrOfConsentResponse($applicant, 'rejected');

        return redirect()->route('resort.consent.show', $token)
            ->with('success', 'Your data will be removed as per your request.');
    }

    /**
     * Neither approve() nor reject() notified anyone — HR only found out an
     * applicant had responded by manually checking the Talent Pool. Same
     * resort-HR lookup pattern used for compliance notifications elsewhere
     * (Employee rank 3 = HR).
     */
    private function notifyHrOfConsentResponse(Applicant_form_data $applicant, string $status): void
    {
        // rank=3 doesn't reliably mean "HR" — this resort's actual HR
        // department employees are rank 1/2, not 3, so the lookup always
        // found nobody and this notification silently never fired.
        // Common::FindResortHR() is the established department-based
        // lookup used everywhere else in the app for this exact purpose.
        $hr = Common::FindResortHR($applicant->resort_id);
        if (!$hr) {
            return;
        }

        $name = trim($applicant->first_name . ' ' . $applicant->last_name);
        $title = $status === 'rejected' ? 'Consent Rejected' : 'Consent Approved';
        $message = "{$name} has {$status} their data-retention consent request.";

        Common::sendMobileNotification(
            $applicant->resort_id,
            1,
            null,
            null,
            $title,
            $message,
            'Talent Acquisition (Consent Response)',
            [$hr->id],
            null,
            false,
            'talent-acquisition-consent-response'
        );
    }
}
