<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceReviewType extends Model
{
    use HasFactory;
    protected $table = 'performance_review_types';
    protected $fillable = [
        'resort_id','category_title','category_weightage'
    ];
}
