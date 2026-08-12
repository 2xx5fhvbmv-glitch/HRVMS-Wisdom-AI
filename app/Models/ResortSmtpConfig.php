<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Auth;

class ResortSmtpConfig extends Model
{
    use HasFactory;

    protected $table = 'resort_smtp_configs';
    protected $fillable = [
        'resort_id', 'host', 'port', 'username', 'password', 'encryption', 'from_address', 'from_name',
    ];

    public static function boot()
    {
        parent::boot();

        self::saving(function ($model) {
            if (!$model->exists) {
                $model->created_by = Auth::guard('resort-admin')->user()->id;
            }

            if (Auth::guard('resort-admin')->check()) {
                $model->modified_by = Auth::guard('resort-admin')->user()->id;
            }
        });
    }

    // Laravel 8 predates the built-in 'encrypted' cast — Crypt (APP_KEY-based)
    // gives the same effect via accessor/mutator.
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getPasswordAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }
}
