<?php

namespace App\Models;
use Illuminate\Support\Facades\Auth;
use App\Models\ResortAdmin;
use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
	public $timestamps = false;

	protected $fillable = ['subject', 'name', 'body', 'created_at', 'updated_at'];

	public static function get_string_between($string, $start, $end){
		$string = ' ' . $string;
		$ini = strpos($string, $start);
		if ($ini == 0) return '';
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;
		return substr($string, $ini, $len);
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


}
