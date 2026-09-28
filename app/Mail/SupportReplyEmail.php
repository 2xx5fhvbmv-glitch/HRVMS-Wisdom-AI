<?php
namespace App\Mail;

use App\Helpers\StorageHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

use App\Models\Support;
use App\Models\SupportMessages;
use App\Models\Resort;
use App\Models\EmailTemplate;


class SupportReplyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $resort;
    public $replyMessage;
    public $replyBy;
    public $messageId;

    public function __construct(Support $ticket, Resort $resort, $replyMessage, $replyBy, $messageId = null)
    {

        $this->ticket = $ticket;
        $this->resort = $resort;
        $this->replyMessage = $replyMessage;
        $this->replyBy = $replyBy; // Can be Admin or Employee who replied
        $this->messageId = $messageId;
        //  dd($this->ticket->id);
    }

    public function build()
    {
        $emailPage = 'emails.commonEmail';

        $support = Support::with(['support_category','createdBy','assignedAdmin'])
                        ->where('id', $this->ticket->id)
                        ->first();
        $cleanMessage = strip_tags($this->replyMessage);
        // dd($this->replyMessage,$this->ticket->id);

        // Looked up by id, not by matching the message text — a text match
        // silently broke (and null-derefed $support_message->sender below)
        // whenever the stored message differed from what's passed in here
        // (e.g. once the resort side started passing the already-stripped
        // message instead of the raw request body).
        $support_message = $this->messageId
            ? SupportMessages::find($this->messageId)
            : SupportMessages::where('ticket_id', $this->ticket->id)
                ->where('message', $this->replyMessage)
                ->first();
        // dd($support_message);
        if(optional($support_message)->sender == "admin"){
            $employeeName = $support->createdBy->first_name . " " . $support->createdBy->last_name;
            $replyByName = $this->replyBy;
        }
        else{
            $employeeName = $support->assignedAdmin->first_name . " ".$support->assignedAdmin->last_name;
            $replyByName = $this->replyBy;
        }
       
        // Fetch Email Template
        $emailTemplate = EmailTemplate::find(config('settings.email_template.support_reply_email'));

        $subjectLine = isset($emailTemplate) && $emailTemplate->subject != '' ? 
                    $emailTemplate->subject : 'Support Ticket Reply: [Subject]';

        $defaultBody = "<p> Hello [Employee Name] </p>
        <p>A new reply has been added to your support ticket.</p>
        <p>[Reply Message]</p><p>Please log in to the system to review the ticket.</p>
        <p>Regards,</p>
        <p>[Reply By].</p>";

        $data['body'] = isset($emailTemplate) && $emailTemplate->body != '' ? 
                        $emailTemplate->body : $defaultBody;

        // Replace placeholders with actual values. Reply message is escaped
        // here — it's printed raw ({!! !!}) by emails.commonEmail below, and
        // is either an admin's e()-escaped text or an employee's stripped
        // (but entity-encoded) text; without re-escaping, an entity-encoded
        // payload decodes right back into live HTML in the recipient's inbox.
        $placeholders = ["[Subject]","[Employee Name]", "[Reply Message]", "[Reply By]"];
        $values = [ $this->ticket->subject,$employeeName, nl2br(e($this->replyMessage)), $replyByName];

        $subject = str_replace($placeholders, $values, $subjectLine);
        $data['mainbody'] = str_replace($placeholders, $values, $data['body']);

        $mail = $this->subject($subject)
                    ->view($emailPage)
                    ->with($data);

        // 🔹 Attach files if available
        if(!empty($this->ticket->attachments)){
            foreach(json_decode($this->ticket->attachments, true) as $attachment){
                if(isset($attachment['Filename']) && isset($attachment['Child_id'])){
                   // Assuming 'Filename' contains the S3 key
                    $s3Key = $attachment['Filename'];

                    // Get file content from configured cloud disk
                    if (StorageHelper::exists($s3Key)) {
                        $tempFile = tempnam(sys_get_temp_dir(), 'mail_attachment_');
                        file_put_contents($tempFile, StorageHelper::get($s3Key));

                        $mail->attach($tempFile, [
                            'as' => basename($s3Key),
                            'mime' => StorageHelper::mimeType($s3Key),
                        ]);
                    } else {
                        \Log::warning("Attachment not found in storage: $s3Key");
                    }
                }
            }
        }

        return $mail;
    }

}
