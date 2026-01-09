<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Carbon\Carbon;
class AccommodationType extends Model
{


        use HasFactory;
        protected $table = 'accommodation_types';
        protected $fillable = [
                                    'resort_id',
                                    'AccommodationName',
                                    'Color',
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

        public function availableAccommodations()
        {
            return $this->hasMany(AvailableAccommodationModel::class, 'Accommodation_type_id', 'id');
        }

}
