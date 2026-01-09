<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeesDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'resort_id',
        'document_title',
        'document_path',
        'document_category',
        'document_file_size',
        'created_by',
        'modified_by',
    ];


    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

}


