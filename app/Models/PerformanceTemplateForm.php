<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceTemplateForm extends Model
{
    use HasFactory;

    protected $table = 'performance_template_forms';
    public $fillable = ['FormName','resort_id','Department_id','Division_id','Section_id','Position_id','form_structure'];

}
