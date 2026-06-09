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
        // Was 'employee_id ' (with trailing space). The space made every
        // Compliance::create([..., 'employee_id' => $x, ...]) silently
        // drop the FK — explains the historical "row created but
        // employee_id is NULL" rows on live.
        'employee_id',
        'module_name',
        'compliance_breached_name',
        'description',
        'reported_on',
        'status',
        'Dismissal_status',
        'assigned_to',
        // AI-generated context fields populated by ComplianceController
        // after each rule fires. Nullable everywhere — view falls back
        // to the hardcoded `description` when these are empty.
        'description_ai',
        'remediation_ai',
        'severity_ai',
        'ai_status',
        'ai_generated_at',
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
