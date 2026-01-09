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

class InvoiceSendEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $user;
    public $id;
    public function __construct($user, $id)
    {
        $this->user = $user;
        $this->id = $id;
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

        $emailTemplate = EmailTemplate::where('name','=', 'Invoice Send Email')->first();

        if (!$emailTemplate) {
            $subject = 'Your Invoice PDF';
            $body = "<p>[textAboveLink]</p>

            <p>You are receiving this email because you submit a sample for testing.so here is your system generated invoice</p>

            <p>[/textAboveLink]</p>
            </p> [textBelowLink] </p>

            <p>Thanks</p>
            <p>BUV International</p>

            <p>[/textBelowLink]</p>";
        }
        else{
            $subject = $emailTemplate->subject;
            $body = $emailTemplate->body;
        }
    
        $settings = Settings::first();
        $encryptedID = encrypt($this->id);
        $data['siteLogo'] = $settings ? $settings->site_logo : '';
        $data['facebookLink'] = $settings->facebook_link;
        $data['instagramLink'] = $settings->instagram_link;
        $data['website'] = $settings->website;
        $data['user'] =  $this->user;
        // $data['downloadPDF'] = route('admin.quotations.pdf', ['quotation_no' => $this->quotation_no]);
        $data['downloadPDF'] = route('admin.samples.invoice', ['id' => $encryptedID]);
        $data['aboveBody'] = EmailTemplate::get_string_between($body, '[textAboveLink]', '[/textAboveLink]');
        $data['belowBody'] = EmailTemplate::get_string_between($body, '[textBelowLink]', '[/textBelowLink]');
        // dd($data);
        return $this->view('emails.sendInvoice', $data)->subject(Lang::get($subject));
    }
}
