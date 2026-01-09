<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Models\BudgetStatus;
use App\Models\ResortDepartment;
class ManningResponse extends Model
{
    use HasFactory;

    // Specify the table name if it's not the plural form of the model name
    protected $table = 'manning_responses';

    // Specify the primary key if it's not the default 'id'
    protected $primaryKey = 'id';

    // Allow mass assignment for these fields
    protected $fillable = [
        'resort_id','dept_id',
        'year',
        'month',
        'total_filled_positions',
        'total_vacant_positions',
        'total_headcount',
        'budget_process_status'
    ];

    public static function boot(){
        parent::boot();
    }

    public function getCreatedAtAttribute($value): ?string {
      if($value == '') {
        return '';
      } else {
        $dateFormat = Common::getDateFormateFromSettings();
        $timezone = config('app.timezone');
        $timeFormat = Common::getTimeFromSettings() == '12' ? 'h:i A' : 'H:i';
        $format = $dateFormat . ' ' . $timeFormat;
        return Carbon::parse($value)->setTimezone($timezone)->format($format);
      }
    }

    public function getUpdatedAtAttribute($value): ?string {
      if($value == '') {
        return '';
      } else {
        $dateFormat = Common::getDateFormateFromSettings();
        $timezone = config('app.timezone');
        $timeFormat = Common::getTimeFromSettings() == '12' ? 'h:i A' : 'H:i';
        $format = $dateFormat . ' ' . $timeFormat;
        return Carbon::parse($value)->setTimezone($timezone)->format($format);
      }
    }

    public function positionMonthlyData()
    {
        return $this->hasMany(PositionMonthlyData::class);
    }


    public function GetBudgetStatus()
    {
        return $this->hasMany( BudgetStatus::class,'Budget_id','id');
    }
    public function department()
    {
        return $this->belongsTo(ResortDepartment::class, 'dept_id');
    }
}
