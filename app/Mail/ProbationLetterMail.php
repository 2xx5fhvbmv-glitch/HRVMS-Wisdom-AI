<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Employee;
use App\Models\Resort;
use App\Models\ProbationLetterTemplate;

use Illuminate\Support\Facades\Storage;

class ProbationLetterMail extends Mailable
{ 
    use Queueable, SerializesModels;

    public $employee;
    protected $pdfPath, $fileName;
    public $type;
    public $resort;

    /**
     * Create a new message instance.
     */
    public function __construct(Employee $employee, $pdfPath, $type, Resort $resort, $fileName)
    {
        $this->employee = $employee;
        $this->pdfPath = $pdfPath;
        $this->type = $type;
        $this->resort = $resort;
         $this->fileName = $fileName;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        // dd($this->type);
        $subject = $this->type === 'success'
            ? 'Probation Confirmation Letter'
            : 'Probation Unsuccessful Letter';

        // Fetch the template from the database
        $template = ProbationLetterTemplate::where('resort_id', $this->employee->resort_id)
            ->where('type', $this->type)
            ->first();

        // If template doesn't exist, return an error (you could handle this differently as well)
        if (!$template) {
            return response()->json(['error' => 'Template not found for this resort and type.'], 404);
        }

        $probationEndDate = \Carbon\Carbon::parse($this->employee->probation_end_date)->format('d M Y');

        // Define the placeholders and their replacements
        $placeholders = [
            '{{employee_name}}'       => (string) optional($this->employee->resortAdmin)->full_name,
            '{{position_title}}'      => (string) optional($this->employee->position)->position_title,
            '{{resort_name}}'         => (string) $this->resort->resort_name,
            '{{probation_end_date}}'  => $probationEndDate,
            '{{date}}'                => now()->format('d M Y'),
            '{{employment_type}}'     => (string) $this->employee->employment_type,
            '{{position}}'            => (string) optional($this->employee->position)->position_title,
        ];

        // Replace placeholders with actual values
        $letterContent = strtr($template->content, $placeholders);
        $emailPage = 'emails.commonEmail';


        $mail = $this->subject($subject)
                    ->view($emailPage)
                    ->with(['mainbody' => $letterContent]);
        
        // 🔹 Attach files if available
        if (!empty($this->pdfPath)) {
            $mail->attach($this->pdfPath,[
                'as' => $this->fileName,
                'mime' => 'application/pdf',
            ]); // Attach each file
        }

        // Now, pass the processed content into the common email view
        return $mail;

    }

}
