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
            // Guarded the same way as modified_by below — this model is now
            // also written from the API (mobile) guard's accommodation
            // assignment endpoint, which never authenticates 'resort-admin',
            // so the unconditional ->user()->id here would null-fatal there.
            if (!$model->exists && Auth::guard('resort-admin')->check()) {
                $model->created_by = Auth::guard('resort-admin')->user()->id;
            }

            if(Auth::guard('resort-admin')->check()) {
                $model->modified_by = Auth::guard('resort-admin')->user()->id;
            }
        });
    }
}
