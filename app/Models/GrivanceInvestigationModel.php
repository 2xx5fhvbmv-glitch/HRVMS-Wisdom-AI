<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrivanceInvestigationModel extends Model
{
    use HasFactory;
    protected $table = 'grivance_investigation_models';
    public $fillable = ['resort_id','Grievance_s_id','Committee_id','inves_start_date','resolution_date','investigation_files'];
}
