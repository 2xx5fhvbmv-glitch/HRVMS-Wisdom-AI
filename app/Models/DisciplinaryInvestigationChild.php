<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisciplinaryInvestigationChild extends Model
{
    use HasFactory;
    public $table="disciplinary_investigation_children";
    public $fillable = ['resort_id','Disciplinary_P_id','inves_find_recommendations','follow_up_action','follow_up_description','investigation_stage','resolution_note'];
}