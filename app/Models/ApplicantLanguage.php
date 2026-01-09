<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Applicant_form_data;


class ApplicantLanguage extends Model
{
    use HasFactory;
    protected $fillable = [
        'applicant_form_id',
        'language',
        'level',
    ];

    // Relationship to the Applicant model (assuming you have an Applicant model)
    public function applicant()
    {
        return $this->belongsTo(Applicant_form_data::class);
    }
}
