<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResortReports extends Model
{
    use HasFactory;
    public $table="resort_reports";
    protected $fillable = ['resort_id','name', 'from_date','to_date','description', 'query_params', 'user_id','AiInsights'];

    // JSON encode/decode for query_params
    protected $casts = [
        'query_params' => 'array',
    ];
      
   
}
