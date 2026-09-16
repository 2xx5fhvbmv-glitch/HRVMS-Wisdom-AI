<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyCheckinReview extends Model
{
    use HasFactory;
    protected $table = 'monthly_checkin_reviews';

    protected $fillable = [
        'resort_id', 'monthly_checkin_id', 'round', 'area_of_improvement', 'hod_comment',
        'initiated_by', 'initiated_at', 'employee_comment', 'employee_response',
        'decline_reason', 'responded_at',
    ];

    protected $casts = [
        'initiated_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function monthlyCheckin()
    {
        return $this->belongsTo(MonthlyCheckingModel::class, 'monthly_checkin_id');
    }
}
