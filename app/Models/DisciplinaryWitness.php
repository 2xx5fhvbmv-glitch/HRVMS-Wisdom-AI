<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisciplinaryWitness extends Model
{
    use HasFactory;
    
    public $table="disciplinary_witnesses";
    public $fillable = ['resort_id','Disciplinary_id','Employee_id','Statement','Attachement','Wintness_Status'];
}
