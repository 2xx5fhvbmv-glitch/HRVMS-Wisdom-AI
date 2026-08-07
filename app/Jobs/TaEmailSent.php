<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;  // Add this line to import Mail facade
use App\Helpers\Common;

class TaEmailSent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $to = '';
    public $subject = '';
    public $data = [];
    public $resort_id = null;

    public function __construct($recipientEmail, $subject, $data, $resort_id = null)
    {
        $this->to = $recipientEmail;
        $this->subject = $subject;
        $this->data = $data;
        $this->resort_id = $resort_id;
    }

    public function handle()
    {
        // Queue workers run in a separate process from the web request, so
        // the request-time config() override (ApplyResortSmtpConfig
        // middleware) never reaches this — has to be re-applied here.
        Common::applyResortSmtpConfig($this->resort_id);

        // Sending the email
        // Mail::send([], [], function ($message) {
        //     $message->to($this->to)    // Use the recipient's email
        //             ->subject($this->subject)
        //             ->setBody($this->body, 'text/html'); // Ensure the body is HTML
        // });
        Mail::send('emails.commonEmail', $this->data, function ($message) {
            $message->to($this->to)    // Use the recipient's email
                    ->subject($this->subject); // Use the subject

            // Optionally, you can also set a "from" address if needed
            // $message->from('no-reply@example.com', 'Your App Name');
        });
    }
}
