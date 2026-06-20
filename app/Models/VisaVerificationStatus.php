<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisaVerificationStatus extends Model
{
    protected $table = 'visa_verification_statuses';

    protected $fillable = [
        'resort_id',
        'employee_id',
        'status',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
