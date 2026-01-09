<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Carbon\Carbon;
class AvailableAccommodationModel extends Model
{
    use HasFactory;

    protected $table = 'available_accommodation_models';
    public $fillable = [
        'resort_id','BuildingName',	'Floor'	,'RoomNo','Accommodation_type_id','RoomType','BedNo','blockFor','Capacity','CleaningSchedule','RoomStatus','Occupancytheresold','created_by','modified_by'
    ];
 
    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
            if (!$model->exists) {
                $model->created_by = Auth::guard('resort-admin')->user()->id;
            }

            if(Auth::guard('resort-admin')->check()) {
                $model->modified_by = Auth::guard('resort-admin')->user()->id;
            }
        });
    }

    public function availableAccommodationInvItem()
    {
        return $this->hasMany(AvailableAccommodationInvItem::class, 'Available_Acc_id', 'id');
    }

    public function accommodationType()
    {
        return $this->belongsTo(AccommodationType::class, 'Accommodation_type_id', 'id');
    }

    public function assignedAccommodations()
    {
        return $this->hasMany(AssingAccommodation::class, 'available_a_id', 'id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'created_by', 'id');
    }

    public function building()
    {
        return $this->belongsTo(BuildingModel::class, 'BuildingName', 'id');
    }
}
