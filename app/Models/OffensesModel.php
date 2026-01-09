<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\ResortAdmin;
use Carbon\Carbon;
use App\Helpers\Common;
class OffensesModel extends Model
{
    use HasFactory;
    public $table='offenses_models';
    protected $fillable = [
        'resort_id',
        'disciplinary_cat_id',
        'OffensesName',
        'offensesdescription',
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
    public function disciplinaryCategory()
    {
        return $this->belongsTo(DisciplinaryCategoriesModel::class, 'disciplinary_cat_id', 'id');
    }
}
