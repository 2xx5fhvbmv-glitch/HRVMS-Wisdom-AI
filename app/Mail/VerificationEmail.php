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

class VerificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     * 
     */
    public $user;
    
    public function __construct($user)
    {
        // dd($user);
        $this->user = $user;
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

        $emailTemplate = EmailTemplate::where('name','=', 'verification mail')->first();

        if (!$emailTemplate) {
            $subject = 'Verification mail';
            $body = "<p>[textAboveLink]</p>

            <p>Your account has been created, please activate your account by clicking this link</p>

            <p>[/textAboveLink] [textBelowLink]</p>

            <p>Thanks</p>

            <p>[/textBelowLink]</p>";
        }
        else{
            $subject = $emailTemplate->subject;
            $body = $emailTemplate->body;
        }
    
        $settings = Settings::first();
        $data['siteLogo'] = $settings ? $settings->site_logo : '';
        $data['facebookLink'] = $settings->facebook_link;
        $data['instagramLink'] = $settings->instagram_link;
        $data['website'] = $settings->website;
        $data['user'] =  $this->user;
        $data['aboveBody'] = EmailTemplate::get_string_between($body, '[textAboveLink]', '[/textAboveLink]');
        $data['belowBody'] = EmailTemplate::get_string_between($body, '[textBelowLink]', '[/textBelowLink]');
        return $this->view('emails.verifyEmail', $data)->subject(Lang::get($subject));
    }
}
