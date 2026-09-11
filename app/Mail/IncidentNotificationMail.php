<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Helpers\Common;

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
    protected $resortId;

    public function __construct($recipientName, $subject, $body, array $details = [], $ctaUrl = null, $ctaLabel = null, $resortId = null)
    {
        $this->recipientName = $recipientName;
        $this->emailSubject = $subject;
        $this->body = $body;
        $this->details = $details;
        $this->ctaUrl = $ctaUrl;
        $this->ctaLabel = $ctaLabel ?: 'View in HRVMS';
        $this->resortId = $resortId;
    }

    public function build()
    {
        // Actually queued (QUEUE_CONNECTION=database) — runs in a worker
        // process that never saw ApplyResortSmtpConfig, so it has to
        // re-apply here itself (same reason TaEmailSent does it).
        Common::applyResortSmtpConfig($this->resortId);

        return $this->subject($this->emailSubject)
            ->view('emails.incident-notification', [
                'recipientName' => $this->recipientName,
                'body'          => $this->body,
                'details'       => $this->details,
                'ctaUrl'        => $this->ctaUrl,
                'ctaLabel'      => $this->ctaLabel,
                // No Auth session in the queue worker — header/footer can't
                // fall back to auth('resort-admin')->user(), pass it directly.
                'resortLogo'    => $this->resortId ? Common::GetResortLogo($this->resortId) : null,
            ]);
    }
}
