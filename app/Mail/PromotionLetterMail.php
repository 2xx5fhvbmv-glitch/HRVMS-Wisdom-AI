<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\EmployeePromotion;
use App\Models\Resort;
use App\Models\ProbationLetterTemplate;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\ResortAdmin;

class PromotionLetterMail extends Mailable
{ 
    use Queueable, SerializesModels;
    public $promotion;
    public $pdfPath;
    public $type;
    public $resort;

    /**
     * Create a new message instance.
     */
    public function __construct(EmployeePromotion $promotion, $pdfPath, $type, Resort $resort)
    {
        $this->promotion = $promotion;
        $this->pdfPath = $pdfPath;
        $this->type = $type;
        $this->resort = $resort;
    }

    /**
     * Build the message.
     */
    public function build()
    {
       
        // Fetch the template from the database
        $template = ProbationLetterTemplate::where('resort_id', $this->promotion->resort_id)
            ->where('type', $this->type)
            ->first();

        // If template doesn't exist, return an error (you could handle this differently as well)
        if (!$template) {
            return response()->json(['error' => 'Template not found for this resort and type.'], 404);
        }
        $subject = $template->subject ?? 'Promotion Letter'; // Default subject if not set in template

        // Define the placeholders and their replacements
        $placeholders = [
            '{{employee_name}}'       => (string) optional($this->promotion->employee->resortAdmin)->full_name,
            '{{employee_code}}'       => (string) $this->promotion->employee->Emp_id,
            '{{position_title}}'      => (string) optional($this->promotion->currentPosition)->position_title,
            '{{Department_title}}'   => (string) optional($this->promotion->currentPosition->department)->name,
            '{{resort_name}}'         => (string) $this->resort->resort_name,
            '{{date}}'                => now()->format('d M Y'),
            '{{employment_type}}'     => (string) $this->promotion->employee->employment_type,
            '{{new_position}}'         => (string) optional($this->promotion->newPosition)->position_title,
            '{{current_department}}' => (string) optional($this->promotion->currentPosition->department)->name,
            '{{new_department}}' => (string) optional($this->promotion->newPosition->department)->name,
            '{{new_level}}' => (string) $this->promotion->new_level,
            '{{effective_date}}' => (string) Carbon::parse($this->promotion->effective_date)->format('d M Y'),
        ];

        // Email body = template->email_body (the short notification HR
        // configured). PDF content is already baked into $pdfPath and
        // attached below. Falls back to a generated boilerplate when the
        // template predates the email_body column (legacy rows still
        // need to send) — placeholders still get substituted so
        // {{employee_name}} resolves correctly in the fallback too.
        $defaultEmailBody = '<p>Dear {{employee_name}},</p>'
            . '<p>Please find your ' . e($subject) . ' attached.</p>'
            . '<p>Regards,<br>{{resort_name}} HR</p>';
        $emailBodyTemplate = !empty($template->email_body) ? $template->email_body : $defaultEmailBody;
        $emailBody = strtr($emailBodyTemplate, $placeholders);

        return $this->view('emails.commonEmail')
            ->with(['mainbody' => $emailBody])
            ->subject($subject)
            ->attach(Storage::path($this->pdfPath), [
                'as' => 'Promotion_Letter_' . ($this->promotion->resortAdmin->full_name ?? 'Employee') . '.pdf',
                'mime' => 'application/pdf',
            ]);
    }

}
