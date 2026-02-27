<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use App\Models\ApplicantInterViewDetails;
use App\Models\Applicant_form_data;
use App\Models\ResortAdmin;
use App\Models\Vacancies;
use App\Models\Resort;
use App\Jobs\TaEmailSent;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InterviewInvitationController extends Controller
{
    public function show($token)
    {
        $interview = ApplicantInterViewDetails::where('invitation_token', $token)->first();

        if (!$interview) {
            return view('resorts.interview_invitation.show', [
                'error' => 'Invalid invitation link.',
                'interview' => null,
            ]);
        }

        // Get applicant, vacancy and resort details
        $applicant = Applicant_form_data::find($interview->Applicant_id);
        $resort = Resort::find($interview->resort_id);

        $vacancy = Vacancies::join('resort_positions as t1', 't1.id', '=', 'vacancies.position')
            ->join('resort_departments as t2', 't2.id', '=', 't1.dept_id')
            ->where('vacancies.id', $applicant->Parent_v_id ?? 0)
            ->selectRaw('t1.position_title as position, t2.name as department')
            ->first();

        return view('resorts.interview_invitation.show', [
            'error' => null,
            'interview' => $interview,
            'applicant' => $applicant,
            'resort' => $resort,
            'vacancy' => $vacancy,
            'token' => $token,
        ]);
    }

    public function accept($token)
    {
        $interview = ApplicantInterViewDetails::where('invitation_token', $token)->first();

        if (!$interview) {
            return redirect()->route('resort.interview.invitation.show', $token)
                ->with('error', 'Invalid invitation link.');
        }

        if ($interview->Status === 'Slot Booked') {
            return redirect()->route('resort.interview.invitation.show', $token)
                ->with('info', 'You have already accepted this invitation.');
        }

        if ($interview->Status === 'Invitation Rejected') {
            return redirect()->route('resort.interview.invitation.show', $token)
                ->with('error', 'This invitation has already been declined.');
        }

        if ($interview->Status !== 'Invitation Sent') {
            return redirect()->route('resort.interview.invitation.show', $token)
                ->with('error', 'This invitation is no longer valid.');
        }

        $interview->update(['Status' => 'Slot Booked']);

        // Send notification email to interviewer
        $this->notifyInterviewer($interview, 'accepted');

        return redirect()->route('resort.interview.invitation.show', $token)
            ->with('success', 'Interview invitation accepted successfully!');
    }

    public function reject(Request $request, $token)
    {
        $interview = ApplicantInterViewDetails::where('invitation_token', $token)->first();

        if (!$interview) {
            return redirect()->route('resort.interview.invitation.show', $token)
                ->with('error', 'Invalid invitation link.');
        }

        if ($interview->Status === 'Invitation Rejected') {
            return redirect()->route('resort.interview.invitation.show', $token)
                ->with('info', 'You have already declined this invitation.');
        }

        if ($interview->Status === 'Slot Booked') {
            return redirect()->route('resort.interview.invitation.show', $token)
                ->with('error', 'This invitation has already been accepted and cannot be declined.');
        }

        if ($interview->Status !== 'Invitation Sent') {
            return redirect()->route('resort.interview.invitation.show', $token)
                ->with('error', 'This invitation is no longer valid.');
        }

        $interview->update([
            'Status' => 'Invitation Rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        // Send notification email to interviewer
        $this->notifyInterviewer($interview, 'declined', $request->rejection_reason);

        return redirect()->route('resort.interview.invitation.show', $token)
            ->with('success', 'Interview invitation declined.');
    }

    /**
     * Send notification email to the interviewer about candidate's response
     */
    private function notifyInterviewer($interview, $action, $reason = null)
    {
        try {
            $interviewer = $interview->interviewer_id ? ResortAdmin::find($interview->interviewer_id) : null;
            if (!$interviewer || !$interviewer->email) {
                return;
            }

            $applicant = Applicant_form_data::find($interview->Applicant_id);
            $resort = Resort::find($interview->resort_id);
            $candidateName = $applicant ? ucfirst($applicant->first_name) . ' ' . ucfirst($applicant->last_name) : 'Unknown';
            $interviewDate = Carbon::parse($interview->InterViewDate)->format('d M Y');
            $resortName = $resort->resort_name ?? '';

            if ($action === 'accepted') {
                $subject = "Interview Accepted - {$candidateName}";
                $body = "
                    <p>Dear Interviewer,</p>
                    <p>We are pleased to inform you that <strong>{$candidateName}</strong> has <strong style='color:#198754;'>accepted</strong> the interview invitation.</p>
                    <table style='width:100%;border-collapse:collapse;margin:16px 0;'>
                        <tr><td style='padding:8px;border:1px solid #ddd;font-weight:600;'>Candidate</td><td style='padding:8px;border:1px solid #ddd;'>{$candidateName}</td></tr>
                        <tr><td style='padding:8px;border:1px solid #ddd;font-weight:600;'>Interview Date</td><td style='padding:8px;border:1px solid #ddd;'>{$interviewDate}</td></tr>
                        <tr><td style='padding:8px;border:1px solid #ddd;font-weight:600;'>Resort Time</td><td style='padding:8px;border:1px solid #ddd;'>{$interview->ResortInterviewtime}</td></tr>
                    </table>
                    <p>Please proceed with sharing the meeting link for this interview.</p>
                    <p>Regards,<br>HR Team<br>{$resortName}</p>
                ";
            } else {
                $subject = "Interview Declined - {$candidateName}";
                $reasonHtml = $reason ? "<tr><td style='padding:8px;border:1px solid #ddd;font-weight:600;'>Reason</td><td style='padding:8px;border:1px solid #ddd;'>{$reason}</td></tr>" : '';
                $body = "
                    <p>Dear Interviewer,</p>
                    <p>We regret to inform you that <strong>{$candidateName}</strong> has <strong style='color:#dc3545;'>declined</strong> the interview invitation.</p>
                    <table style='width:100%;border-collapse:collapse;margin:16px 0;'>
                        <tr><td style='padding:8px;border:1px solid #ddd;font-weight:600;'>Candidate</td><td style='padding:8px;border:1px solid #ddd;'>{$candidateName}</td></tr>
                        <tr><td style='padding:8px;border:1px solid #ddd;font-weight:600;'>Interview Date</td><td style='padding:8px;border:1px solid #ddd;'>{$interviewDate}</td></tr>
                        <tr><td style='padding:8px;border:1px solid #ddd;font-weight:600;'>Resort Time</td><td style='padding:8px;border:1px solid #ddd;'>{$interview->ResortInterviewtime}</td></tr>
                        {$reasonHtml}
                    </table>
                    <p>You may wish to reschedule or assign a new candidate for this time slot.</p>
                    <p>Regards,<br>HR Team<br>{$resortName}</p>
                ";
            }

            TaEmailSent::dispatch($interviewer->email, $subject, ['mainbody' => $body]);
        } catch (\Exception $e) {
            \Log::warning("Failed to notify interviewer: " . $e->getMessage());
        }
    }
}
