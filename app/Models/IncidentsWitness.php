<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;
class IncidentsWitness extends Model
{
    use HasFactory;

    protected $table="incidents_witness";

    protected $fillable = [
        'incident_id',
        'witness_id',
        'witness_statements',
        'witness_status',
        'witness_statement_file'  
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

    public function incidents()
    {
        return $this->belongsTo(Incidents::class, 'incident_id', 'id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'witness_id', 'id');
    }

   
}
