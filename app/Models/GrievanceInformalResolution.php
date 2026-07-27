<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrievanceInformalResolution extends Model
{
    use HasFactory;

    protected $table = 'grievance_informal_resolutions';

    protected $fillable = [
        'resort_id',
        'employee_id',
        'resolved_informally',
        'description',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
