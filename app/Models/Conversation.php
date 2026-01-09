<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Departmen;
use App\Models\Position;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;

class Conversation extends Model
{
    use HasFactory,Notifiable;
    use SoftDeletes;
    protected $table="conversation";
    protected $guarded = ['id'];

    
    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
            if (!$model->exists && Auth::guard('resort-admin')->check()) {
                $model->created_by = Auth::guard('resort-admin')->user()->id;
            }

            if (Auth::guard('resort-admin')->check()) {
                $model->modified_by = Auth::guard('resort-admin')->user()->id;
            }
        });
    }

   
}
