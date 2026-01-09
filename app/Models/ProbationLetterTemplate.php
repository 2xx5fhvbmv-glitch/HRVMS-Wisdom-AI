<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Departmen;
use App\Models\Position;
use Illuminate\Notifications\Notifiable;
use Auth;
use Carbon\Carbon;
use App\Helpers\Common;

class ProbationLetterTemplate extends Model
{
  use HasFactory;

  public $table="probation_letter_templates";
  public $fillable = ['resort_id','type','subject','content','placeholders'];

  protected $casts = [
      'placeholders' => 'array', 
  ];

  public static function extractPlaceholders($body)
  {
      preg_match_all('/{{(.*?)}}/', $body, $matches);
      return $matches[1]; 
  }
}