<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\FinalSettlement;

/**
 * Sends the finalized Full and Final Settlement PDF to the employee and
 * any extra CC recipients (Finance, HR copy, etc.) the controller
 * passes in.
 *
 * The actual PDF is generated server-side by the same dompdf view the
 * payroll-run module uses, then attached. Body text is rendered from
 * `emails.commonEmail` (same shell every other transactional email
 * uses) so styling stays consistent.
 */
class FinalSettlementMail extends Mailable
{
    use Queueable, SerializesModels;

    public FinalSettlement $finalSettlement;
    public string $pdfPath;
    public string $employeeName;
    public string $resortName;
    public ?string $referenceNo;

    public function __construct(FinalSettlement $finalSettlement, string $pdfPath)
    {
        $this->finalSettlement = $finalSettlement;
        $this->pdfPath         = $pdfPath;
        $this->employeeName    = optional(optional($finalSettlement->employee)->resortAdmin)->full_name
            ?? 'Employee';
        $this->resortName      = optional(optional($finalSettlement->employee)->resort)->resort_name
            ?? 'the Resort';
        $this->referenceNo     = $finalSettlement->reference_no ?? null;
    }

    public function build()
    {
        $body = '<p>Dear ' . e($this->employeeName) . ',</p>'
            . '<p>Please find attached your <strong>Final Pay Settlement</strong>'
            . ($this->referenceNo ? ' (' . e($this->referenceNo) . ')' : '')
            . ' from ' . e($this->resortName) . '.</p>'
            . '<p>This settlement reflects all dues — basic salary for the worked period, '
            . 'leave encashment, allowances and service charge — net of statutory and '
            . 'voluntary deductions (EWHT, MRPS pension, loan / advance recovery, '
            . 'notice-period charge, custom deductions). If you have any questions, '
            . 'reply to this email and HR will follow up.</p>'
            . '<p>We wish you all the best in your next role.</p>';

        $filename = 'Final_Settlement_'
            . preg_replace('/[^A-Za-z0-9_-]/', '_', $this->referenceNo ?? ('FS-' . $this->finalSettlement->id))
            . '.pdf';

        $mail = $this->view('emails.commonEmail')
            ->with(['mainbody' => $body])
            ->subject('Final Pay Settlement — ' . $this->resortName);

        if (is_file($this->pdfPath)) {
            $mail->attach($this->pdfPath, [
                'as'   => $filename,
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
