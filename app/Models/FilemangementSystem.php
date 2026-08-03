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
        'is_system_generated',
        'created_by',
        'modified_by',
    ];

    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
            // Mobile API requests authenticate via the 'api' guard, not
            // 'resort-admin' — calling ->user()->id here unguarded threw
            // "Attempt to read property 'id' on null" for any upload flow
            // that creates a folder row from the mobile app (e.g. the
            // auto-created sub-folder in AWSEmployeeFileUpload).
            $user = Auth::guard('api')->user() ?? Auth::guard('resort-admin')->user();

            if ($user) {
                if (!$model->exists) {
                    $model->created_by = $user->id;
                }
                $model->modified_by = $user->id;
            }
        });
    }

    /**
     * Files directly stored under this folder. Enables withCount('children')
     * and withSum('children', 'File_Size') so listing pages can avoid N+1.
     */
    public function children()
    {
        return $this->hasMany(ChildFileManagement::class, 'Parent_File_ID', 'id');
    }
}
