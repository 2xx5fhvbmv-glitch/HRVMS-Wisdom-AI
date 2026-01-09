<?php

namespace App\Models;

use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisciplinaryCategoriesModel extends Model
{
    use HasFactory;

    public $table='disciplinary_categories_models';
    public $fillable=['resort_id','DisciplinaryCategoryName','description','created_by','modified_by'];

       
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
    public function offenses()
    {
        return $this->hasMany(OffensesModel::class, 'disciplinary_cat_id', 'id');
    }
}
