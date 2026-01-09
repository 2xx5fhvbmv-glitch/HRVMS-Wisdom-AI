<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
class Incidents extends Model
{
    use HasFactory;

    protected $table="incidents";

    protected $fillable = [
        'resort_id',
        'incident_id',
        'incident_name',
        'description',
        'reporter_id',
        'victims',
        'location',
        'category',
        'subcategory',
        'incident_date',
        'incident_time',
        'isWitness',
        'involved_employees',
        'attachements',
        'status',
        'priority',
        'assigned_to',
        'severity',
        'comments',
        'outcome_type',
        'action_taken',
        'approval',
        'approved_by',
        'approved_at',
        'approval_remarks',
        'preventive_measures',
        'created_by',
        'modified_by',
        'resolved_by',
        'resolved_at',
    ];
    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
            $user = Auth::guard('api')->user() ?? Auth::guard('resort-admin')->user();

            if ($user) {
                if (!$model->exists) {
                    $model->created_by = $user->id;
                }
                $model->modified_by = $user->id;
            }
      });
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

    public function witness()
    {
        return $this->hasMany(IncidentsWitness::class, 'incident_id', 'id');
    }

    public function meeting()
    {
        return $this->hasMany(IncidentsMeeting::class, 'incident_id', 'id');
    }

    public function categoryName()
    {
        return $this->belongsTo(IncidentCategory::class, 'category', 'id');
    }

    public function subcategoryName()
    {
        return $this->belongsTo(IncidentSubCategory::class, 'subcategory', 'id');
    }

    public function reporter()
    {
        return $this->belongsTo(Employee::class, 'reporter_id', 'id');
    }

    public function victim()
    {
        return $this->belongsTo(Employee::class, 'victims', 'id');
    }

    public function Investigation()
    {
        return $this->hasMany(IncidentsInvestigation::class, 'incident_id', 'id');
    }

    public function employeeStatements()
    {
        return $this->hasMany(IncidentsEmployeeStatements::class, 'incident_id', 'id');
    }
    
}
