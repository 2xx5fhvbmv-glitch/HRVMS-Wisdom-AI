<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Lang;
use App\Models\Admin;
use App\Models\Users;
use App\Models\Employees;
use App\Models\Customers;
use App\Models\Shopkeeper;
use App\Models\EmailTemplate;
use App\Models\Settings;
use App\Helpers\Common;
use App\Models\ResortAdmin;

class ResetPassword extends ResetPasswordNotification
{
  use Queueable;
  public $token;
  public $user;

  public function __construct($token,$user)
  {
    // dd($token, $user);
    $this->token = $token;
    $this->user = $user;
  }

  public function via($notifiable)
  {
    return ['mail'];
  }

  public function toMail($notifiable)
  {

    $email = $notifiable->getEmailForPasswordReset();
    // dd($email);

    switch($this->user)
    {
      case "admin":
        $user = Admin::where('email','=', $email)->first();

        $name = $user->first_name." ".$user->last_name;


      break;
      case "resort":
        $user = ResortAdmin::where('email', $email)->first();

        $name = $user->first_name.' '.$user->last_name;

        // dd($name);
      break;
      case "shopkeeper":
        $user = Shopkeeper::where('email', $email)->first();

        $name = $user->name;
      break;
      case "default":
        $name = null;
      break;
    }

    if(!$name) return false;

    $emailTemplate = EmailTemplate::where('name','=', 'Reset Password')->first();

    if (!$emailTemplate) {
      $subject = 'Reset Password | HRVMS-WIsdomAI';

      $body = "<p>[textAboveLink]</p>

      <p>You are receiving this email because we received a password reset request for your account.</p>

      <p>[/textAboveLink] [textBelowLink]</p>

      <p>This password reset link will expire in 60 minutes.</p>

      <p>If you did not request a password reset, no further action is required.</p>

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

    $data['token'] = $this->token;
    $data['user'] =  $this->user;
    $data['name'] =  $name;
    $data['aboveBody'] = EmailTemplate::get_string_between($body, '[textAboveLink]', '[/textAboveLink]');
    $data['belowBody'] = EmailTemplate::get_string_between($body, '[textBelowLink]', '[/textBelowLink]');
    $data['resetUrl'] = url('/').route($this->user . '.password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()], false);
    return (new MailMessage)
    ->view(
      'emails.passwordEmail', $data
    )
    ->subject(Lang::get($subject));
  }

  public function toArray($notifiable)
  {
    return [];
  }
}
