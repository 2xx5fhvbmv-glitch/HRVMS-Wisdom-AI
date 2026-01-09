<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssingAccommodation extends Model
{
    use HasFactory;



    protected $table='assing_accommodations';
    public $fillable = ['resort_id','available_a_id','emp_id','BedNo'];

    public function availableAccommodation()
    {
        return $this->belongsTo(AvailableAccommodationModel::class, 'available_a_id', 'id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->BedNo)) {
                $lastBed = self::where('resort_id', $model->resort_id)
                              ->orderByDesc('id')
                              ->value('BedNo'); // Get the last BedNo for this resort_id

                $nextNumber = 1; // Default starting number

                if ($lastBed) {
                    preg_match('/\d+$/', $lastBed, $matches); // Extract numeric part
                    $lastNumber = $matches[0] ?? 0;
                    $nextNumber = (int) $lastNumber + 1;
                }

                $model->BedNo = 'BedNo-' . $nextNumber;
            }
        });


    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'id');
    }
}
