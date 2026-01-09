<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Carbon\Carbon;
class InventoryModule extends Model
{
    use HasFactory;


    protected $table = 'inventory_modules';
    protected $fillable = ['resort_id','Inv_Cat_id','ItemName','ItemCode','PurchageDate','Occupied','Quantity','MinMumStockQty'];


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

    public function HistoricalInventory()
    {
        return $this->hasMany(MaintanaceRequest::class, 'item_id');
    }

    public function InventoryCategory()
    {
        return $this->belongsTo(InventoryCategoryModel::class,'Inv_Cat_id','id');
    }

}
