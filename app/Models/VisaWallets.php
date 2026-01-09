<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisaWallets extends Model
{
    use HasFactory;


    public $table = 'visa_wallets';
    public $fillable = [
        'resort_id',
        'WalletName',
        'Amt',
        'Payment_Date',
        'Status'
    ];
    public function resort()
    {
        return $this->belongsTo(Resorts::class, 'resort_id');
    }

}
