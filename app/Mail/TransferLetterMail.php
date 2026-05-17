<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\EmployeeTransfer;

/**
 * Item 7 — Emails the generated Transfer Letter PDF to the transferred
 * employee. Mirrors the PromotionLetterMail pattern (commonEmail body view +
 * PDF attachment).
 */
class TransferLetterMail extends Mailable
{
    use Queueable, SerializesModels;

    public $transfer;
    public $pdfPath;       // absolute filesystem path to the generated PDF
    public $bodyHtml;      // pre-rendered email body
    public $employeeName;

    public function __construct(EmployeeTransfer $transfer, string $pdfPath, string $bodyHtml, string $employeeName)
    {
        $this->transfer     = $transfer;
        $this->pdfPath      = $pdfPath;
        $this->bodyHtml     = $bodyHtml;
        $this->employeeName = $employeeName;
    }

    public function build()
    {
        $mail = $this->view('emails.commonEmail')
            ->with(['mainbody' => $this->bodyHtml])
            ->subject('Transfer Letter');

        if (!empty($this->pdfPath) && file_exists($this->pdfPath)) {
            $mail->attach($this->pdfPath, [
                'as'   => 'Transfer_Letter_' . preg_replace('/[^A-Za-z0-9]+/', '_', $this->employeeName) . '.pdf',
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
