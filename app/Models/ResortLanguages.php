<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResortLanguages extends Model
{
    use HasFactory;

    protected $table = "resort_languages";

    public $fillable = ['name','sort_name','native','country_code','flag_image','flag_image_svg'];


}
