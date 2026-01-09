<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Professionalform extends Model
{
    use HasFactory;

    protected $table = 'professionalforms';
    protected $fillable = ['FormName', 'resort_id', 'form_structure'];


}
