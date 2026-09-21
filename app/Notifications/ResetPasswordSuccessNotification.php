<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;
use Illuminate\Notifications\Notification;
use App\Models\Admin;
use App\Models\ResortAdmin;
use App\Models\Shopkeeper;
use App\Models\Employees;
use App\Models\EmailTemplate;
use App\Models\Settings;

class ResetPasswordSuccessNotification extends Notification
{
  use Queueable;
  public $user;
  public $userData;
  public $password;

  public function __construct($user, $userData, $password)
  {
    $this->user = $user;
    $this->userData = $userData;
    $this->password = $password;
  }

  public function via($notifiable)
  {
    return ['mail'];
  }

  public function toMail($notifiable)
  {
    $emailPage = 'emails.commonEmail';

    switch($this->user)
    {
      case "admin":
        $user = Admin::where('email','=', $this->userData->email)->first();
        $name = ucwords($user->first_name." ".$user->last_name);
      break;

      case "resort":
        $user = ResortAdmin::where('email', $this->userData->email)->first();
        $name = ucwords($user->full_name);
      break;

      case "employee":
        $user = Employees::where('email', $this->userData->email)->first();
        $name = ucwords($user->full_name);
      break;

      case "shopkeeper":
        $user = Shopkeeper::where('email', $this->userData->email)->first();
        $name = ucwords($user->name);
      break;

      case "default":
        $name = '';
      break;
    }

    $login_route = route($this->user.'.loginindex');
    $login_button = '<a style="padding:5px 10px;background-color:#DA2128;color:#ffffff" href="'.$login_route.'">Login here</a>';

    $emailTemplate = EmailTemplate::find(config('settings.email_template.password_change_notification'));

    $subjectLine = isset( $emailTemplate ) && $emailTemplate->subject != '' ? $emailTemplate->subject : 'Password Reset | HRVMS Wisdom AI';

    // Never include the new plaintext password in this email — a reset
    // confirmation only, so a compromised mailbox can't be used to recover
    // the account's live password. If a stored EmailTemplate row still has
    // a [Password] placeholder from before this fix, it's substituted with
    // an empty string below (see $yummy) rather than a real password.
    $data['body'] = isset( $emailTemplate ) && $emailTemplate->body != '' ? $emailTemplate->body : "<p>Dear [User Name],</p>

      <p>Your password for [Email] was just changed successfully.</p>

      <p>If you made this change, no further action is needed. If you did NOT request this change, please contact support immediately.</p>

      <p>[Login Url]</p>

      <p>If you encounter any issues or have any questions, please do not hesitate to contact our support team.</p>

      <p>Thank you,</p>";

    $settings = Settings::first();
    $data['siteLogo'] = $settings ? $settings->site_logo : '';
    $data['facebookLink'] = $settings->facebook_link;
    $data['instagramLink'] = $settings->instagram_link;
    $data['website'] = $settings->website;

    $healthy = [
      "[User Name]",
      "[Email]",
      "[Password]",
      "[Login Url]",
    ];

    $yummy = [
      $name,
      $this->userData->email,
      '', // [Password] never substituted with a real value anymore — see note above.
      $login_button,
    ];

    $subject = str_replace( $healthy, $yummy, $subjectLine );
    $data['mainbody'] = str_replace( $healthy, $yummy, $data['body'] );

    $data['settings'] = $settings;

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
