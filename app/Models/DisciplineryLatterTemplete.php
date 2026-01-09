<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisciplineryLatterTemplete extends Model
{
    use HasFactory;
    public $table ='disciplinery_latter_templetes';
    public $fillable = ['resort_id','Latter_Temp_name','Latter_Structure'];
}
