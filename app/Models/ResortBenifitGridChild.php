<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\ResortAdmin;
use Carbon\Carbon;
use App\Helpers\Common;
class ResortBenifitGridChild extends Model
{
    use HasFactory;
    protected $table = 'resort_benefit_grid_child';
    protected $fillable = [
        "benefit_grid_id",	"leave_cat_id",	"rank",	"allocated_days","eligible_emp_type"
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

      public function leaveCategory() {
        return $this->belongsTo(LeaveCategory::class, 'leave_cat_id');
      }
}
