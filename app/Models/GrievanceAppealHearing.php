<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class GrievanceAppealHearing extends Model
{
    use HasFactory;

    protected $table = 'grievance_appeal_hearings';

    protected $fillable = [
        'appeal_id',
        'sequence_no',
        'hearing_date',
        'hearing_time',
        'location',
        'status',
        'participants',
        'outcome_notes',
        'completed_at',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'participants' => 'array',
        'hearing_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            if (Auth::guard('resort-admin')->check()) {
                $model->created_by = Auth::guard('resort-admin')->user()->id;
            }
            // Auto-fill sequence_no per appeal if caller didn't set it.
            if (empty($model->sequence_no) && $model->appeal_id) {
                $model->sequence_no = static::where('appeal_id', $model->appeal_id)->count() + 1;
            }
        });
        self::saving(function ($model) {
            if (Auth::guard('resort-admin')->check()) {
                $model->modified_by = Auth::guard('resort-admin')->user()->id;
            }
        });
    }

    public function appeal()
    {
        return $this->belongsTo(GrievanceAppeal::class, 'appeal_id', 'id');
    }
}
