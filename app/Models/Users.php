<?php
namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Notifications\ResetPassword;
use App\Notifications\ResetPasswordSuccessNotification;
use App\Notifications\AdminEnquiryNotification;
use App\Notifications\AccountVerificationNotification;
use App\Notifications\ReviewNotification;
use App\Notifications\AccountDeactivateNotification;
use App\Notifications\InvoiceNotification;

class Users extends Authenticatable
{
  use Notifiable;
  use SoftDeletes;

  protected $table = 'users';

  protected $fillable = [
    'email', 'first_name', 'last_name', 'middle_name', 'phone', 'password', 'birthdate', 'gender', 'profile_pic', 'status', 'description', 'activation_date', 'last_logged_in', 'is_account_closed', 'last_seen', 'is_online', 'country_code', 'country', 'street', 'landmark', 'city', 'state', 'zipcode', 'password_updated', 'customer_id', 'payment_method', 'username'
  ];

  protected $hidden = [
    'password', 'remember_token', 'email_verified_at'
  ];

  protected $dates = ['created_at','updated_at','deleted_at'];

  public function sendPasswordResetNotification($token)
  {
    $this->notify(new ResetPassword($token,"user"));
  }

  public function sendPasswordResetSuccessNotification($admin)
  {
    $this->notify(new ResetPasswordSuccessNotification("user", $admin));
  }

  public function sendAccountVerificationNotification($student)
  {
    $this->notify(new AccountVerificationNotification( $student ) );
  }

  public function sendReviewNotification($user, $tour, $rating)
  {
    $this->notify(new ReviewNotification($user, $tour, $rating) );
  }

  public function sendAccountDeactivateNotification($user)
  {
    $this->notify(new AccountDeactivateNotification($user) );
  }

  public function sendInvoiceNotification($user, $invoice)
  {
    $this->notify(new InvoiceNotification($user, $invoice) );
  }
}
