<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

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
    public function __construct($subject,$body)
    {
        $this->subject = $subject;
        $this->body= $body;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->subject('Performance Meeting Invitation')
                     ->view('emails.commonEmail') // Blade template
                     ->with(['mainbody' => $this->body]);
    }
}
