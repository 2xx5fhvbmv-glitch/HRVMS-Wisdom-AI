<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HousekeepingRequest extends Model
{
    use HasFactory;
    protected $table = 'housekeeping_requests';

    protected $fillable = [
        'resort_id', 'request_id', 'batch_id', 'employee_id', 'housekeeping_service_id',
        'raised_by', 'BuildingName', 'FloorNo', 'RoomNo', 'remarks', 'status', 'completed_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->request_id)) {
                $model->request_id = 'HK-' . strtoupper(uniqid());
            }
        });
    }

    public function service()
    {
        return $this->belongsTo(HousekeepingServiceCatalog::class, 'housekeeping_service_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
