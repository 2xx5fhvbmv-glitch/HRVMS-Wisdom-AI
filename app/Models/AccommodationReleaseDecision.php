<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccommodationReleaseDecision extends Model
{
    protected $table = 'accommodation_release_decisions';

    protected $fillable = ['resort_id', 'employee_id', 'decision', 'next_check_on', 'decided_by', 'decided_at', 'last_error'];

    protected $casts = ['next_check_on' => 'date', 'decided_at' => 'datetime'];

    public const OPEN = ['pending', 'keep'];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
