<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EscalationDay extends Model
{
    use HasFactory;
    protected $table = 'escalation_days';

    protected $fillable = [
                            'resort_id',
                            'EscalationDay'
                        ];
}
