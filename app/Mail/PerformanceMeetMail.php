<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Helpers\Common;

class PerformanceMeetMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $body;
    public $subject;
    public $resort_id;
    public function __construct($subject,$body,$resort_id = null)
    {
        $this->subject = $subject;
        $this->body= $body;
        $this->resort_id = $resort_id;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // ->queue() runs on a separate queue-worker process, so the
        // request-time config() override (ApplyResortSmtpConfig
        // middleware) never reaches this — re-apply here.
        Common::applyResortSmtpConfig($this->resort_id);

        return $this->subject('Performance Meeting Invitation')
                     ->view('emails.commonEmail') // Blade template
                     ->with(['mainbody' => $this->body]);
    }
}
