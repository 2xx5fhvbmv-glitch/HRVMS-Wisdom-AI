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

class AdminRegistrationEmail extends ResetPasswordNotification
{
  use Queueable;
  public $data;
  public $admin;
  public $password;

  public function __construct( $admin, $password)
  {
    // $this->data = $data;
    $this->admin = $admin;
    $this->password = $password;
  }

  public function via($notifiable)
  {
    return ['mail'];
  }

  public function toMail($notifiable)
  {
    $admin = $this->admin;

    $emailPage = 'emails.commonEmail';

    $settings = Settings::first();
    $data['siteLogo'] = $settings ? $settings->header_logo : '';
    $data['facebookLink'] = $settings->facebook_link;
    $data['instagramLink'] = $settings->instagram_link;
    $data['website'] = $settings->website;

    $user_name = ucwords($this->admin->first_name.' '.$this->admin->last_name);
    // $resort_name = ucwords($this->data->resort_name);
    $email = $this->admin->email;
    $password = $this->password;

    $login_route = route('admin.loginindex');
    $login_button = '<a style="padding:5px 10px;background-color:#DA2128;color:#ffffff" href="'.$login_route.'">Login here</a>';

    $emailTemplate = EmailTemplate::find(config('settings.email_template.admin_registartion_notification'));

    $subjectLine = isset( $emailTemplate ) && $emailTemplate->subject != '' ? $emailTemplate->subject : 'Admin Account Credentials | HRVMS-WisdomAI';

    $data['body'] = isset( $emailTemplate ) && $emailTemplate->body != '' ? $emailTemplate->body : "<p>Dear [User Name],</p>

<p>Welcome to Wisdom AI ! Your account has been successfully registered. Below are your credentials to access your account:</p>

<p><strong>Email:</strong> [Email]</p>
<p><strong>Password:</strong> [Password]</p>

<p>Please keep these credentials safe and do not share them with anyone. You can log in to your account using the following link:</p>

<p>[Login Url]</p>

<p>If you encounter any issues or have any questions, please do not hesitate to contact our support team.</p>

<p>Thank you,</p>";

    $healthy = [
      "[User Name]",
      "[Email]",
      "[Password]",
      "[Login Url]",
    ];

    $yummy = [
      $user_name,
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
