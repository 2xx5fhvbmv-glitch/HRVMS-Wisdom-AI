<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Employee;
use App\Models\Resort;
use App\Models\ProbationLetterTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class EmployementCertificateMail extends Mailable
{
    use Queueable, SerializesModels;

    public $employee;
    public $pdfPath;
    public $type;
    public $resort;
    /**
     * Create a new message instance.
     *
     * @return void
     */
     public function __construct(Employee $employee, $pdfPath, $type, Resort $resort,$employeeResignation)
    {
        $this->employee = $employee;
        $this->pdfPath = $pdfPath;
        $this->type = $type;
        $this->resort = $resort;
        $this->employeeResignation = $employeeResignation;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $employee = $this->employee;
        $resort = $this->resort;
        $type = $this->type;
        $pdfPath = $this->pdfPath;
        $employeeResignation = $this->employeeResignation;

      $subject = $this->type === 'success'
        ? 'Experience Certificate'
        : 'Experience Certificate';

    // Fetch the template from the database
    $template = ProbationLetterTemplate::where('resort_id', $this->employee->resort_id)
        ->where('type', $this->type)
        ->first();


    $placeholders = [
            '{{date}}'                => now()->format('d M Y'),
            '{{resort_name}}'         => (string) $resort->resort_name,
            '{{employee_name}}'       => (string) optional($employee->resortAdmin)->full_name,
            '{{position_title}}'      => (string) optional($employee->position)->position_title,
            '{{Department_title}}'   => (string) optional($employee->department)->name,
            '{{joining_date}}' => (string) Carbon::parse($employee->joining_date)->format('d M Y'),
            '{{last_working_day}}' => (string) Carbon::parse($employeeResignation->last_working_day)->format('d M Y'),
    ];

    // Replace placeholders with actual values
    $letterContent = strtr($template->content, $placeholders);
   
     // Return email with PDF attachment
    return $this->view('emails.commonEmail')
        ->with(['mainbody' => $letterContent])
        ->subject($subject)
        ->attach($pdfPath, [
            'as' => 'Experience_Certificate_' . ($this->employee->Emp_id ?? 'Employee') . '.pdf',
            'mime' => 'application/pdf',
        ]);

    // // Now, pass the processed content into the common email view
    // return $this->view('emails.commonEmail')
    //     ->with(['mainbody' => $letterContent]) // Pass the dynamic letter content
    //     ->subject($subject)
    //     ->attach(Storage::path($this->pdfPath), [
    //         'as' => 'Probation_Letter_' . ($this->employee->resortAdmin->full_name ?? 'Employee') . '.pdf',
    //         'mime' => 'application/pdf',
    //     ]);
}
}
