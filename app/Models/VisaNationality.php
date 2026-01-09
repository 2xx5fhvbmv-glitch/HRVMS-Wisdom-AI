<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisaNationality extends Model
{
    use HasFactory;
    protected  $table = 'visa_nationalities';
    public  $fillable = ["resort_id" ,"nationality", "amt"];
}
