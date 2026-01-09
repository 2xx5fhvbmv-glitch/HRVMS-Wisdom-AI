<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Lang;
use App\Models\Admin;
use App\Models\Users;
use App\Models\Employees;
use App\Models\Customers;
use App\Models\EmailTemplate;
use App\Models\Settings;
use App\Helpers\Common;
use Illuminate\Support\Facades\Crypt;

class QuotationPdfEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $user;
    public $quotation_no;
    public function __construct($user, $quotation_no)
    {
        $this->user = $user;
        $this->quotation_no = $quotation_no;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    { 
        switch($this->user)
        {
            case "admin":
                $user = Admin::where('email','=', $email)->first();
                $name = $user->first_name." ".$user->last_name;
                break;

            case "user":
                $user = Users::where('email', $email)->first();
                $name = $user->first_name.' '.$user->last_name;
                break;

            case "employees":
                $user = Employees::where('email', $email)->first();
                $name = $user->first_name.' '.$user->last_name;
                break;

            case "customers":
                $user = Customers::where('email', $email)->first();
                $name = $user->full_name;
                break;
        
            case "default":
                $name = null;
                break;
        }

        $emailTemplate = EmailTemplate::where('name','=', 'Quotation PDF Email')->first();

        if (!$emailTemplate) {
            $subject = 'Your Quotation PDF';
            $body = "<p>[textAboveLink]</p>

            <p>You are receiving this email because we received a quotation request from your account.</p>

            <p>[/textAboveLink] </p>
            
            <p>[textBelowLink]</p>

            <p>Thanks</p>

            <p>BUV International</p>

            <p>[/textBelowLink]</p>";
        }
        else{
            $subject = $emailTemplate->subject;
            $body = $emailTemplate->body;
        }
    
        $settings = Settings::first();
        $encryptedQuotationNo = encrypt($this->quotation_no);
        $data['siteLogo'] = $settings ? $settings->site_logo : '';
        $data['facebookLink'] = $settings->facebook_link;
        $data['instagramLink'] = $settings->instagram_link;
        $data['website'] = $settings->website;
        $data['user'] =  $this->user;
        // $data['downloadPDF'] = route('admin.quotations.pdf', ['quotation_no' => $this->quotation_no]);
        $data['downloadPDF'] = route('admin.quotations.pdf', ['quotation_no' => $encryptedQuotationNo]);
        $data['aboveBody'] = EmailTemplate::get_string_between($body, '[textAboveLink]', '[/textAboveLink]');
        $data['belowBody'] = EmailTemplate::get_string_between($body, '[textBelowLink]', '[/textBelowLink]');
        // dd($data);
        return $this->view('emails.sendQuotation', $data)->subject(Lang::get($subject));
    }
}
