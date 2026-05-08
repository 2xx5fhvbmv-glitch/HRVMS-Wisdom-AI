<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class GrievanceAppeal extends Model
{
    use HasFactory;

    protected $table = 'grievance_appeals';

    protected $fillable = [
        'resort_id',
        'grievance_id',
        'appeal_no',
        'submitted_by',
        'submitted_at',
        'status',
        'decision',
        'decision_at',
        'decided_by',
        'reason',
        'decision_notes',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'decision_at'  => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            if (Auth::guard('resort-admin')->check()) {
                $model->created_by = Auth::guard('resort-admin')->user()->id;
            }
            // Generate a sequential appeal_no per resort if one wasn't set.
            if (empty($model->appeal_no) && $model->resort_id) {
                $count = static::where('resort_id', $model->resort_id)->count() + 1;
                $model->appeal_no = 'APL-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
            }
        });
        self::saving(function ($model) {
            if (Auth::guard('resort-admin')->check()) {
                $model->modified_by = Auth::guard('resort-admin')->user()->id;
            }
        });
    }

    public function grievance()
    {
        return $this->belongsTo(GrivanceSubmissionModel::class, 'grievance_id', 'id');
    }

    public function hearings()
    {
        return $this->hasMany(GrievanceAppealHearing::class, 'appeal_id', 'id')
            ->orderBy('sequence_no');
    }

    public function submitter()
    {
        return $this->belongsTo(Employee::class, 'submitted_by', 'id');
    }
}
