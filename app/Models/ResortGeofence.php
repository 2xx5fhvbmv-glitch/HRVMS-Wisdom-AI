<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;

class ResortGeofence extends Model
{
    use HasFactory;

    protected $table = 'resort_geofences';

    protected $fillable = [
        'resort_id', 'name', 'color', 'shape_type', 'coordinates',
        'grace_period', 'status', 'created_by', 'modified_by'
    ];

    public static function boot()
    {
        parent::boot();

        self::saving(function ($model) {
            if (!$model->exists) {
                $model->created_by = Auth::guard('resort-admin')->user()->id ?? null;
            }
            if (Auth::guard('resort-admin')->check()) {
                $model->modified_by = Auth::guard('resort-admin')->user()->id;
            }
        });
    }

    public function resort()
    {
        return $this->belongsTo(Resort::class, 'resort_id');
    }
}
