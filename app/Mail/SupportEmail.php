<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Support;
use App\Models\Resort;
use App\Models\EmailTemplate;


class SupportEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $resort;

    public function __construct(Support $ticket, Resort $resort)
    {
        $this->ticket = $ticket;
        $this->resort = $resort;
    }

    public function build()
    {
        $emailPage = 'emails.commonEmail';

        $support = Support::with(['support_category','createdBy','assignedAdmin'])
                        ->where('id', $this->ticket->id)
                        ->first();

        $employeeName = $support->createdBy->first_name . " " . $support->createdBy->last_name;

        // Fetch Email Template
        $emailTemplate = EmailTemplate::find(config('settings.email_template.support_email'));

        $subjectLine = isset($emailTemplate) && $emailTemplate->subject != '' ? 
                    $emailTemplate->subject : 'New Support Ticket from [Employee Name]-[Resort Name]';

        $defaultBody = "<p>New Support Ticket Raised</p>]<br>
                        <p><strong>Resort Name:</strong>[Resort Name]</p>]<br>
                        <p><strong>Employee Name:</strong>[Employee Name]</p>]<br>
                        <p><strong>Subject:</strong>[Subject]</p>]<br>
                        <p><strong>Description:</strong>[Description]</p>]<br>
                        <p><strong>Status:</strong>[Status]</p>]<br>
                        <p>Please log in to the system to review the ticket</p>";

        $data['body'] = isset($emailTemplate) && $emailTemplate->body != '' ? 
                        $emailTemplate->body : $defaultBody;

        // Replace placeholders with actual values
        $placeholders = ["[Resort Name]", "[Employee Name]", "[Subject]", "[Description]", "[Status]"];
        $values = [$this->resort->resort_name, $employeeName, $this->ticket->subject, $this->ticket->description, $this->ticket->status];

        $subject = str_replace($placeholders, $values, $subjectLine);
        $data['mainbody'] = str_replace($placeholders, $values, $data['body']);

        $mail = $this->subject($subject)
                    ->view($emailPage)
                    ->with($data);

        // 🔹 Attach files if available
        if (!empty($this->ticket->attachments)) {
            $attachments = json_decode($this->ticket->attachments, true);
            if (is_array($attachments)) {
                foreach ($attachments as $filePath) {
                    $mail->attach(public_path($filePath)); // Attach each file
                }
            }
        }

        return $mail;
    }

}
