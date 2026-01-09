<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRecommendation extends Model
{
    use HasFactory;

    protected $table = 'leave_recommendations';

    protected $fillable = [
        'leave_id',
        'recommended_by',
        'alt_start_date',
        'alt_end_date',
        'comments',
    ];

    /**
     * Get the leave request associated with the recommendation.
     */
    public function leave()
    {
        return $this->belongsTo(EmployeeLeave::class, 'leave_id');
    }

    /**
     * Get the user who recommended the alternative dates.
     */
    public function recommender()
    {
        return $this->belongsTo(Employee::class, 'recommended_by');
    }
}
