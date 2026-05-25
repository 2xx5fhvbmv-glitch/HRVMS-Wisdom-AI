<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the candidate when an Offline Interview is finalized with
 * is_selected = Yes. Attaches every uploaded offer letter / contract
 * file in a single message — see OfflineInterviewController::finalize().
 */
class OfflineOfferLetterMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $candidateName;
    public string $positionTitle;
    public string $resortName;
    /** @var array<int, array{path:string, name:string, mime:string}> */
    public array $files;

    public function __construct(string $candidateName, string $positionTitle, string $resortName, array $files)
    {
        $this->candidateName = $candidateName;
        $this->positionTitle = $positionTitle;
        $this->resortName    = $resortName;
        $this->files         = $files;
    }

    public function build()
    {
        $subject = 'Your Offer Letter — ' . ($this->positionTitle ?: 'New Role');

        $mail = $this->subject($subject)
            ->view('emails.offline_offer_letter');

        foreach ($this->files as $f) {
            if (empty($f['path']) || !file_exists($f['path'])) continue;
            $mail->attach($f['path'], [
                'as'   => $f['name'] ?? basename($f['path']),
                'mime' => $f['mime'] ?? 'application/octet-stream',
            ]);
        }

        return $mail;
    }
}
