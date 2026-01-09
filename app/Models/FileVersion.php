<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
class FileVersion extends Model
{
    use HasFactory;
    protected $table = 'file_versions';
    
    protected $fillable = [
        'resort_id',
        'file_id',
        'version_number',
        'file_path',
        'uploaded_by',
        'uploaded_at',
        'created_by',
        'modified_by'	
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
