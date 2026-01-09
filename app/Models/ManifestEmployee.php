<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManifestEmployee extends Model
{
    use HasFactory;
    protected $table = 'manifest_employees';

    protected $fillable = [
        'manifest_id',
        'employee_id',
    ];

    public function manifest()
    {
        return $this->belongsTo(Manifest::class,'manifest_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class,'employee_id'); // Make sure you have Employee model
    }
}
