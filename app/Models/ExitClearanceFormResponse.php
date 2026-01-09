<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Carbon\Carbon;

class ExitClearanceFormResponse extends Model
{
    use HasFactory;
    protected $table="exit_clearance_form_responses";
    protected $guarded = ['id'];
    public  $fillable = ['assignment_id','response_data','submitted_by','submitted_date'];


    public function formAssignment(){
      return $this->belongsTo(ExitClearanceFormAssignment::class, 'assignment_id', 'id');  
    }

    
}
