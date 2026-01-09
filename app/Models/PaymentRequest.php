<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class PaymentRequest extends Model
{
    use HasFactory;
    protected $table = 'payment_requests';
    protected $fillable = [
        'resort_id',
        'Requestd_id',
        'Request_date','Reason',
        'Status'];
  
            
}
