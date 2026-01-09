<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
class IncidentsInvestigation extends Model
{
    use HasFactory;

    protected $table="incidents_investigation";

    protected $fillable = [
        'incident_id',
        'committee_id',
        'police_notified',
        'police_date',
        'police_time',
        'mdf_notified',
        'mndf_date',
        'mndf_time',
        'fire_rescue_notified',
        'fire_rescue_date',
        'fire_rescue_time',
        'start_date',
        'expected_resolution_date',
        'investigation_findings',
        'folloup_action',
        'resolution_notes',
        'created_by',
        'added_by_member_id',
        'Ministry_notified',
        'Ministry_notified_date'
    ];
    public static function boot(){
        parent::boot();

        self::saving(function ($model) {
          if (!$model->exists) {
              $model->created_by = Auth::guard('resort-admin')->user()->id;
          }

          // if(Auth::guard('resort-admin')->check()) {
          //     $model->modified_by = Auth::guard('resort-admin')->user()->id;
          // }
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

    public function addedBy()
    {
        return $this->belongsTo(IncidentCommitteeMember::class, 'added_by_member_id', 'id');
    }

    public function followupAction()
    {
        return $this->belongsTo(IncidentFollowupActions::class, 'folloup_action', 'id');
    }

   
}
