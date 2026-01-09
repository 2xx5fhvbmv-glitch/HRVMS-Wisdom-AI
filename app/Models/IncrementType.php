<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;


class IncrementType extends Model
{
    use HasFactory;
    protected $table ='increment_types';
    protected $guarded = ['id'];

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

    public function resortAdmin()
    {
        return $this->belongsTo(ResortAdmin::class, 'resort_id', 'id');
    }

}
