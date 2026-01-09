<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Lang;
use App\Models\Shopkeeper;
use App\Models\Product;
use App\Models\Resort;
use App\Models\Employee;
use App\Models\EmailTemplate;
use App\Models\Settings;
use App\Helpers\Common;
use Auth;
use Log;

class ConsentProductPurchaseNotification extends ResetPasswordNotification
{
  use Queueable;
  public $payment;
  
  public $shopkeeper;

  public function __construct($payment, $shopkeeper)
  {
    // dd($payment, $shopkeeper);
    $this->payment = $payment;
    $this->shopkeeper = $shopkeeper;
  }

  public function via($notifiable)
  {
    return ['mail'];
  }

  public function toMail($notifiable)
  {
   
    $shopkeeper = Auth::guard('shopkeeper')->user()->id;
    // dd( $this->shopkeeper );
    $emailPage = 'emails.commonEmail';

    $settings = Settings::first();
    $data['siteLogo'] = $settings ? $settings->header_logo : '';
    $data['facebookLink'] = $settings->facebook_link;
    $data['instagramLink'] = $settings->instagram_link;
    $data['website'] = $settings->website;

    $employee = Employee::with('resortAdmin')->where('id',$this->payment->emp_id)->get();
    $emp_name = $employee[0]->resortAdmin->first_name." ".$employee[0]->resortAdmin->last_name;
    $product = Product::where('id',$this->payment->product_id)->first();
    // dd($employee );
    $email =  $employee[0]->email;
    $consent_route = route('payroll.payment.consent', ['employee_id' => base64_encode($this->payment->emp_id)]);  
    $consent_button = '<a style="padding:5px 10px;background-color:#DA2128;color:#ffffff" href="'.$consent_route.'">Consent Sent to Shopkeeper</a>';

    $emailTemplate = EmailTemplate::find(config('settings.email_template.consent_product_purchase_notification'));

    $subjectLine = isset( $emailTemplate ) && $emailTemplate->subject != '' ? $emailTemplate->subject : 'Shopkeeper Account Credentials | HRVMS-WisdomAI';

    $data['body'] = isset( $emailTemplate ) && $emailTemplate->body != '' ? $emailTemplate->body : "<p>Dear [Employee Name],</p><p>You have requested to purchase the following product:</p><p><strong>Product Name:</strong> [Product Name]<br><strong>Quantity:</strong> [Quantity]<br><strong>Price:</strong> [Total Price]<br><strong>Order ID:</strong> [Order ID]<br><strong>Purchase Date:</strong> [Purchase Date]</p><p>By proceeding with this purchase, you confirm that you acknowledge the details above and agree to any applicable deductions or payments related to this transaction.</p><p>If you approve this purchase, please confirm your consent by clicking the link below:</p><p>[Confirm Purchase]</p><p>If you did not request this purchase or have any concerns, please contact the admin immediately.</p><p>Best regards,<br>[Shopkeeper Name]<br><br></p>";

    $healthy = [
      "[Employee Name]",
      "[Product Name]",
      "[Quantity]",
      "[Total Price]",
      "[Order ID]",
      "[Purchase Date]",
      "[Confirm Purchase]",
      "[Shopkeeper Name]"
    ];

    $yummy = [
      $emp_name,
      $product->name,
      $this->payment->quantity,
      $this->payment->price,
      $this->payment->order_id,
      $this->payment->purchased_date,
      $consent_button,
      $this->shopkeeper->name
    ];

    $subject = str_replace( $healthy, $yummy, $subjectLine );
    $data['mainbody'] = str_replace( $healthy, $yummy, $data['body'] );

    $data['settings'] = $settings;

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
