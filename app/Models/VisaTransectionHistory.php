<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use App\Models\Resort;
use App\Models\VisaWallets;

class VisaTransectionHistory extends Model
{
    use HasFactory;

    public $table = 'visa_transection_histories';
    protected $fillable = [
        'resort_id',
        'to_wallet',
        'from_wallet',
        'Amt',
        'to_wallet_realAmt',
        'from_wallet_realAmt',
        'Payment_Date',
        'file',
        'comments',
        'ToEmployee',
        'Employee_id',
    ];
    protected $casts = [
        'Payment_Date' => 'date',
        'Amt' => 'decimal:2',
        'to_wallet_realAmt' => 'decimal:2',
        'from_wallet_realAmt' => 'decimal:2'
    ];
    public function toWallet()
    {
        return $this->belongsTo(VisaWallets::class, 'to_wallet');
    }
    public function fromWallet()
    {
        return $this->belongsTo(VisaWallets::class, 'from_wallet');
    }
    public function toWalletChildren()
    {
        return $this->hasMany(VisaWallets::class, 'to_wallet');
    }
    public function fromWalletChildren()
    {
        return $this->hasMany(VisaWallets::class, 'from_wallet');
    }
    public function resort()
    {
        return $this->belongsTo(Resort::class);
    }
    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) 
        {
            $resort = Resort::find($model->resort_id);
            if ($resort && $resort->name) 
            {
                $initials = collect(explode(' ', $resort->name))->map(fn($word) => strtoupper(substr($word, 0, 1)))->implode('');
            } 
            else
            {
                $initials = 'XXX'; 
            }

            $model->transaction_id = $initials . '-' . now()->format('YmdHis');
        });
    }
}
