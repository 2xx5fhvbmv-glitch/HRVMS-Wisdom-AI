<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisaXpactAmounts extends Model
{
    use HasFactory;
    protected $table = 'visa_xpact_amounts';
    protected $fillable = [
        'resort_id',
        'Xpact_WalletName',
        'Xpact_Amt',
        'Xpact_Payment_Date',
    ];
    protected $casts = [
        'Xpact_Amt' => 'decimal:2',
        'Xpact_Payment_Date' => 'date',
    ];
    public function resort()
    {
        return $this->belongsTo(Resort::class, 'resort_id');
    }
    public function getFormattedXpactAmtAttribute()
    {
        return number_format($this->Xpact_Amt, 2);
    }
}
