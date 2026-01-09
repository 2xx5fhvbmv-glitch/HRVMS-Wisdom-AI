<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\InventoryModule;
class AvailableAccommodationInvItem extends Model
{
    use HasFactory;


    protected $table='available_accommodation_inv_items';
    public $fillable = ['Available_Acc_id','Item_id'];

    public function inventoryModule()
    {
        return $this->belongsTo(InventoryModule::class, 'Item_id', 'id');
    }
}
