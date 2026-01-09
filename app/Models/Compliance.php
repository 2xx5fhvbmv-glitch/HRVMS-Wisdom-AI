<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Employee;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;


class Compliance extends Model 
{
    use HasFactory , SoftDeletes;
    
  protected $guarded = ['id'];
    
    public $fillable = [
        'resort_id',
        'employee_id ',
        'module_name',
        'compliance_breached_name',
        'description',
        'reported_on',
        'status',
        'Dismissal_status',
        'assigned_to',
       
    ];

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
    /**
     * Get the employee associated with the compliance.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
