<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotaSlotRenewal extends Model
{
    use HasFactory;
      public $table = 'quota_slot_renewals';
    public $fillable = ['ReceiptNumber','PaymentType','Paid_Date','resort_id','employee_id','Month','Amt','Currency','Due_Date','Status','Payment_Date','Reciept_file'];

}
