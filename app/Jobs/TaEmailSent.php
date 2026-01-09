<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;  // Add this line to import Mail facade

class TaEmailSent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $to = '';
    public $subject = '';
    public $data = [];

    public function __construct($recipientEmail, $subject, $data)
    {
        $this->to = $recipientEmail;
        $this->subject = $subject;
        $this->data = $data;
    }

    public function handle()
    {
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
