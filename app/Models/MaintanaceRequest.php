<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintanaceRequest extends Model
{
    use HasFactory;
    protected $table = 'maintanace_requests';

    protected $fillable = [
        'resort_id','date','building_id','item_id','FloorNo','Request_id','RoomNo','Image','Completed_Image','Video','priority','Raised_By','Assigned_To','descriptionIssues','Status','ReasonOnHold','RejactionReason'];


    public function BuilidngData()
    {
        return $this->belongsTo(BuildingModel::class, 'building_id', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->request_id)) {
                $model->request_id = 'REQ-' . strtoupper(uniqid());
            }
        });
    }


    public function ItemData()
    {
        return $this->belongsTo(InventoryModule::class, 'item_id', 'id');
    }
}
