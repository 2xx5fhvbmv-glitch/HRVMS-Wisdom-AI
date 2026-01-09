<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
  protected $table = 'settings';

  protected $fillable = [
    'site_title','header_logo', 'footer_logo', 'site_logo','admin_logo', 'site_favicon', 'email_address', 'contact_number', 'admin_email', 'date_format', 'time_format', 'currency_symbol','address_1','address_2','website','support_email','contents'
  ];

  protected $dates = ['created_at', 'updated_at'];
}

?>
