<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResortBenefitGradeLevelRank extends Model
{
    protected $table = 'resort_benefit_grade_level_ranks';
    protected $fillable = ['resort_id', 'grade_level_id', 'rank'];

    public function gradeLevel()
    {
        return $this->belongsTo(ResortBenefitGradeLevel::class, 'grade_level_id');
    }
}
