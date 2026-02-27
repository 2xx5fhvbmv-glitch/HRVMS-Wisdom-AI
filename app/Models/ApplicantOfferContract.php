<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantOfferContract extends Model
{
    use HasFactory;

    protected $table = 'applicant_offer_contracts';

    protected $fillable = [
        'applicant_id',
        'applicant_status_id',
        'resort_id',
        'type',
        'file_path',
        'status',
        'token',
        'rejection_reason',
        'email_template_id',
        'sent_by',
        'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function applicant()
    {
        return $this->belongsTo(Applicant_form_data::class, 'applicant_id');
    }

    public function applicantStatus()
    {
        return $this->belongsTo(ApplicantWiseStatus::class, 'applicant_status_id');
    }

    public function sentByUser()
    {
        return $this->belongsTo(ResortAdmin::class, 'sent_by');
    }

    public function emailTemplate()
    {
        return $this->belongsTo(TaEmailTemplate::class, 'email_template_id');
    }
}
