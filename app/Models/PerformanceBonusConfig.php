<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceBonusConfig extends Model
{
    use HasFactory;
    protected $table = 'performance_bonus_configs';
    protected $fillable = ['resort_id', 'rank', 'bonus_percentage', 'month', 'year'];
}
