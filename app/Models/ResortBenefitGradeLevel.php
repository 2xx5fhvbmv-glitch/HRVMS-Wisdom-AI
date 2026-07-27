<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ResortBenefitGradeLevel extends Model
{
    protected $table = 'resort_benefit_grade_levels';
    protected $fillable = ['resort_id', 'name', 'status'];

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

    public function ranks()
    {
        return $this->hasMany(ResortBenefitGradeLevelRank::class, 'grade_level_id');
    }
}
