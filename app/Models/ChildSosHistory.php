<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;

class ChildSosHistory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'child_sos_history';

    protected $fillable = [
        'sos_history_id',
        'team_id',
        'status',
        'created_by',
        'modified_by',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
            $user = Auth::guard('api')->user() ?? Auth::guard('resort-admin')->user();

            if ($user) {
                if (!$model->exists) {
                    $model->created_by = $user->id;
                }
                $model->modified_by = $user->id;
            }
        });
    }

    public function sos()
    {
        return $this->belongsTo(SOSHistoryModel::class, 'sos_history_id');
    }

    public function team()
    {
        return $this->belongsTo(SOSTeamManagementModel::class, 'team_id');
    }
}
