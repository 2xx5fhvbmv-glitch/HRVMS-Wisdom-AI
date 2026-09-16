<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class JobDescriptionEmployeeRecord extends Model
{
    use HasFactory;

    protected $table = 'job_description_employee_records';

    protected $fillable = [
        'resort_id',
        'job_description_id',
        'employee_id',
        'employer_name',
        'employer_address',
        'employer_nationality',
        'employer_type_of_work',
        'employee_full_name',
        'employee_permanent_address',
        'employee_current_address',
        'employee_id_card_number',
        'employee_dob',
        'employee_nationality',
        'employer_signature_path',
        'employee_signature_path',
        'status',
        'decline_reason',
        'sent_at',
        'signed_at',
        'resend_count',
        'pdf_path',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'employee_dob' => 'date',
        'sent_at' => 'datetime',
        'signed_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        self::saving(function ($model) {
            $user = Auth::guard('resort-admin')->user() ?? Auth::guard('api')->user();
            if (!$user) return;
            if (!$model->exists) {
                $model->created_by = $user->id;
            }
            $model->modified_by = $user->id;
        });
    }

    public function jobDescription()
    {
        return $this->belongsTo(JobDescription::class, 'job_description_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
