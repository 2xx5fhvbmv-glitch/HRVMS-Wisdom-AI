<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrivanceKeyPerson extends Model
{
    use HasFactory;
    protected $table="grivance_key_people";

    public $fillable = ['resort_id','emp_ids'];
}
