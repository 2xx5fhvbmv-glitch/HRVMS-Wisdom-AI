<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Password;
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

    // S8: no plaintext password over email anymore — a real reset token
    // (same broker/table the Forgot Password flow uses) drives a
    // set-your-password link instead. $this->password is kept only so
    // existing callers (AdminController::store()) don't need signature
    // changes; it's no longer read here.
    $resetToken = Password::broker('admins')->createToken($admin);
    $resetUrl = url('/') . route('admin.password.reset', ['token' => $resetToken, 'email' => $email], false);
    $setPasswordButton = '<a style="padding:5px 10px;background-color:#DA2128;color:#ffffff" href="'.$resetUrl.'">Set your password</a>';

    $login_route = route('admin.loginindex');
    $login_button = '<a style="padding:5px 10px;background-color:#DA2128;color:#ffffff" href="'.$login_route.'">Login here</a>';

    $emailTemplate = EmailTemplate::find(config('settings.email_template.admin_registartion_notification'));

    $subjectLine = isset( $emailTemplate ) && $emailTemplate->subject != '' ? $emailTemplate->subject : 'Admin Account Credentials | HRVMS-WisdomAI';

    $data['body'] = isset( $emailTemplate ) && $emailTemplate->body != '' ? $emailTemplate->body : "<p>Dear [User Name],</p>

<p>Welcome to Wisdom AI ! Your account has been successfully registered. Your login email is [Email]. Click below to set your password:</p>

<p>[Password]</p>

<p>This link will expire in 60 minutes. Once set, you can log in using the following link:</p>

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
      $setPasswordButton,
      $login_button,
    ];

    $subject = str_replace( $healthy, $yummy, $subjectLine );
    $data['mainbody'] = str_replace( $healthy, $yummy, $data['body'] );

    $data['settings'] = $settings;

    // dd($data);

    $mail = (new MailMessage)
    ->from( config('mail.from.address'), config('mail.from.name') )
    ->view( $emailPage, $data )
    ->subject(Lang::get($subject));

    return $mail;
  }

  public function toArray($notifiable)
  {
    return [];
  }
}
