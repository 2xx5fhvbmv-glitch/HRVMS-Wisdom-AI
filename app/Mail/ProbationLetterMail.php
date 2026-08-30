<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Employee;
use App\Models\Resort;

class ProbationLetterMail extends Mailable
{ 
    use Queueable, SerializesModels;

    public $employee;
    protected $pdfPath, $fileName, $letterContent;
    public $type;
    public $resort;

    /**
     * Create a new message instance.
     *
     * The 6th arg lands inside the email body — historically this was the
     * fully substituted letter content (same as the PDF), but since the
     * email-body / PDF-content split it should be the short notification
     * substituted from template->email_body (with a generated fallback
     * when the template predates the column). The PDF attachment is
     * passed separately via $pdfPath. The caller (ProbationController
     * @sendProbationLetter) must run {{placeholder}} substitution before
     * passing — re-rendering here would duplicate the placeholder list
     * and silently leak literal tokens like {{employee_code}} into the
     * email whenever the two lists drift.
     */
    public function __construct(Employee $employee, $pdfPath, $type, Resort $resort, $fileName, $letterContent)
    {
        $this->employee = $employee;
        $this->pdfPath = $pdfPath;
        $this->type = $type;
        $this->resort = $resort;
        $this->fileName = $fileName;
        $this->letterContent = $letterContent;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        // 'experience' is a real caller (EmployeeController::sendEmploymentVerificationLetter)
        // that never went through failProbation()/sendProbationLetter() — it's not a probation
        // outcome at all, so it must not fall into the success/fail binary below. Matches
        // EmployementCertificateMail's existing subject wording for the same letter type used
        // by the offboarding flow (ExitClearanceController::employementCertificate).
        if ($this->type === 'experience') {
            $subject = 'Experience Certificate';
        } else {
            $subject = $this->type === 'success'
                ? 'Probation Confirmation Letter'
                : 'Probation Unsuccessful Letter';
        }

        $mail = $this->subject($subject)
                    ->view('emails.commonEmail')
                    ->with(['mainbody' => $this->letterContent]);

        if (!empty($this->pdfPath)) {
            $mail->attach($this->pdfPath, [
                'as'   => $this->fileName,
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }

}
