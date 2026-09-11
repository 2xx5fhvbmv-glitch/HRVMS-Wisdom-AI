<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BenefitGradeHousekeepingService extends Model
{
    use HasFactory;
    protected $table = 'benefit_grade_housekeeping_services';

    protected $fillable = ['resort_id', 'grade_level_id', 'housekeeping_service_id'];

    public function service()
    {
        return $this->belongsTo(HousekeepingServiceCatalog::class, 'housekeeping_service_id');
    }
}
