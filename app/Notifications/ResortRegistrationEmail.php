<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Lang;
use App\Models\Admin;
use App\Models\EmailTemplate;
use App\Models\Settings;
use App\Helpers\Common;

class ResortRegistrationEmail extends ResetPasswordNotification
{
  use Queueable;
  public $data;
  public $resort_admin;
  public $password;

  public function __construct($data, $resort_admin, $password)
  {
    $this->data = $data;
    
    $this->resort_admin = $resort_admin;
    $this->password = $password;

    
  }

  public function via($notifiable)
  {
    return ['mail'];
  }

  public function toMail($notifiable)
  {
    $resort_admin = $this->resort_admin;

    $emailPage = 'emails.commonEmail';

    $settings = Settings::first();
    $data['siteLogo'] = $settings ? $settings->header_logo : '';
    $data['facebookLink'] = $settings->facebook_link;
    $data['instagramLink'] = $settings->instagram_link;
    $data['website'] = $settings->website;

    $user_name = ucwords($this->resort_admin->first_name.' '.$this->resort_admin->last_name);
    $resort_name = ucwords($this->data->resort_name);
    $email = $this->resort_admin->email;
    $password = $this->password;
    $login_route = route('resort.loginindex');
    $login_button = '<a style="padding:5px 10px;background-color:#DA2128;color:#ffffff" href="'.$login_route.'">Login here</a>';

    $emailTemplate = EmailTemplate::find(config('settings.email_template.resort_registration_notification'));

    $subjectLine = isset( $emailTemplate ) && $emailTemplate->subject != '' ? $emailTemplate->subject : 'Account Credentials | HRVMS-WisdomAI';

    $data['body'] = isset( $emailTemplate ) && $emailTemplate->body != '' ? $emailTemplate->body : "<p>Dear [User Name],</p>
            <p>Welcome to [Resort Name]! Your account has been successfully registered. Below are your credentials to access your account:</p>
            <p><strong>Email:</strong> [Email]</p>
            <p><strong>Password:</strong> [Password]</p>
            <p>Please keep these credentials safe and do not share them with anyone. You can log in to your account using the following link:</p>

            <p>[Login Url]</p>

            <p>If you encounter any issues or have any questions, please do not hesitate to contact our support team.</p>

            <p>Thank you,</p>";

    $healthy = [
      "[User Name]",
      "[Resort Name]",
      "[Email]",
      "[Password]",
      "[Login Url]",
    ];

    $yummy = [
      $user_name,
      $resort_name,
      $email,
      $password,
      $login_button,
    ];

    $subject = str_replace( $healthy, $yummy, $subjectLine );
    $data['mainbody'] = str_replace( $healthy, $yummy, $data['body'] );

    $data['settings'] = $settings;

    // dd($data);

    $mail = (new MailMessage)
    ->from( env("MAIL_FROM_ADDRESS") )
    ->view( $emailPage, $data )
    ->subject(Lang::get($subject));

    return $mail;
  }

  public function toArray($notifiable)
  {
    return [];
  }
}
