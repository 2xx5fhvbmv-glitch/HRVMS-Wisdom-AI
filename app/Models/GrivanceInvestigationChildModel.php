<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrivanceInvestigationChildModel extends Model
{
    use HasFactory;
    protected $table = 'grivance_investigation_child_models';
    public $fillable = ['investigation_p_id','follow_up_action','follow_up_description','inves_find_recommendations','investigation_stage','Grivance_Eexplination_description','Committee_member_id','resolution_note'];

}
