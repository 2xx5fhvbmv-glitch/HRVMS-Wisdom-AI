<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Carbon\Carbon;
use App\Models\Admin;
use App\Helpers\Common;
class FilemangementSystem extends Model
{
    use HasFactory;

    protected $table = 'filemangement_systems';
    
    protected $fillable = [
        'resort_id',
        'Folder_unique_id',
        'UnderON',
        'Folder_Name',
        'Folder_Type',	
        'created_by',
        'modified_by',
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
}
