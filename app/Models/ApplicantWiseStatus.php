<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicantWiseStatus extends Model
{
    use HasFactory;
    protected $table = 'applicant_wise_statuses';
    protected $fillable = [
        'Applicant_id','As_ApprovedBy','status','Comments'
    ];
}
