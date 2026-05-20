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
     * $letterContent must already have every {{placeholder}} substituted by
     * the caller (ProbationController@sendProbationLetter). Re-rendering here
     * would duplicate the placeholder list and silently leak literal tokens
     * (e.g. {{employee_code}}, {{Department_title}}) into the email body
     * whenever the two lists drift.
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
        $subject = $this->type === 'success'
            ? 'Probation Confirmation Letter'
            : 'Probation Unsuccessful Letter';

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
