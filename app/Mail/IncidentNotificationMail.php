<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Generic incident-module notification email — same emails.incident-notification
 * view previously sent via a raw Mail::send($view, $data, $callback) closure,
 * which can't be queued (Mailer::queue() only accepts Mailable instances).
 * Used by IncidentController::notifyByEmail() and
 * IncidentMeetingController's meeting-invite send, both of which loop this
 * per recipient (per committee member / involved employee / witness /
 * meeting participant) and were blocking the request on real SMTP
 * round-trips before this existed.
 */
class IncidentNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $recipientName;
    public $body;
    public $details;
    public $ctaUrl;
    public $ctaLabel;
    protected $emailSubject;

    public function __construct($recipientName, $subject, $body, array $details = [], $ctaUrl = null, $ctaLabel = null)
    {
        $this->recipientName = $recipientName;
        $this->emailSubject = $subject;
        $this->body = $body;
        $this->details = $details;
        $this->ctaUrl = $ctaUrl;
        $this->ctaLabel = $ctaLabel ?: 'View in HRVMS';
    }

    public function build()
    {
        return $this->subject($this->emailSubject)
            ->view('emails.incident-notification', [
                'recipientName' => $this->recipientName,
                'body'          => $this->body,
                'details'       => $this->details,
                'ctaUrl'        => $this->ctaUrl,
                'ctaLabel'      => $this->ctaLabel,
            ]);
    }
}
