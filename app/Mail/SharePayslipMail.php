<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\EmailTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;


class SharePayslipMail extends Mailable
{
  use Queueable, SerializesModels;

    public $employee, $month, $year;

    protected $pdfPath, $fileName;

    public function __construct($employee, $month, $year, $pdfPath, $fileName)
    {
        $this->employee = $employee;
        $this->month = $month;
        $this->year = $year;
        $this->pdfPath = $pdfPath;
        $this->fileName = $fileName;
        // dd($this->month, $this->year, $this->pdfPath, $this->fileName);
    }

    public function build()
    {
        $emailPage = 'emails.commonEmail';

        $emailTemplate = EmailTemplate::find(config('settings.email_template.share_pay_slip'));

         $employeeName = $this->employee->resortAdmin->full_name;

        $subjectLine = isset($emailTemplate) && $emailTemplate->subject != '' ? 
                    $emailTemplate->subject : 'Your Payslip for  [month] - [year]';

        $defaultBody = "Dear [Employee Name],

        We hope this message finds you well.

        Your payslip for the period of [month] [year] is now available. Please find it attached to this email as a PDF document.

        If you have any questions or need clarification, please feel free to contact the HR department.

        Best regards,  

        HR Department";

        $data['body'] = isset($emailTemplate) && $emailTemplate->body != '' ? 
                        $emailTemplate->body : $defaultBody;

        $placeholders = ["[Employee Name]", "[month]", "[year]"];
        $values = [$employeeName, $this->month, $this->year];

        $subject = str_replace($placeholders, $values, $subjectLine);
        $data['mainbody'] = str_replace($placeholders, $values, $data['body']);

        $mail = $this->subject($subject)
                    ->view($emailPage)
                    ->with($data);
        
        // 🔹 Attach files if available
        if (!empty($this->pdfPath)) {
            $mail->attach($this->pdfPath,[
                'as' => $this->fileName,
                'mime' => 'application/pdf',
            ]); // Attach each file
        }

        return $mail;
    }
}
