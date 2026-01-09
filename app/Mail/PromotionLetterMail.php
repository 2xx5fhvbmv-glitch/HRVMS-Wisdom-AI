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

        // Replace placeholders with actual values
        $letterContent = strtr($template->content, $placeholders);

        // Now, pass the processed content into the common email view
        return $this->view('emails.commonEmail')
            ->with(['mainbody' => $letterContent]) // Pass the dynamic letter content
            ->subject($subject)
            ->attach(Storage::path($this->pdfPath), [
                'as' => 'Promotion_Letter_' . ($this->promotion->resortAdmin->full_name ?? 'Employee') . '.pdf',
                'mime' => 'application/pdf',
            ]);
    }

}
