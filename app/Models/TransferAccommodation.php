<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
class TransferAccommodation extends Model
{
    use HasFactory;
    
    protected $table = 'transfer_accommodations';
    protected $fillable = ['resort_id','NewAccommodation_id','OldAccommodation_id','Reason','created_by','modified_by','OldDate', 'NewdDate','Emp_id' ];


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
}
