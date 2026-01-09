<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisciplinaryEmailmodel extends Model
{
    use HasFactory;

    public $table="disciplinary_emailmodels";
    public $fillable = ['resort_id','Action_id','subject','content','Placeholders'];

    protected $casts = [
        'Placeholders' => 'array', 
    ];

    public static function extractPlaceholders($body)
    {
        preg_match_all('/{{(.*?)}}/', $body, $matches);
        return $matches[1]; 
    }
}
