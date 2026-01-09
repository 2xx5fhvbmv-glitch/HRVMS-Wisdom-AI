<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisaEmployeeExpiryData extends Model
{
    use HasFactory;
    protected $table = 'visa_employee_expiry_data';
    protected $fillable = [
                            'resort_id',
                            'employee_id',
                            'File_child_id',
                            'Ai_extracted_data',
                            'DocumentName',
                        ];

                        
    public function resort()
    {
        return $this->belongsTo(Resort::class, 'resort_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function ChildFileManagement()
    {
        return $this->belongsTo(ChildFileManagement::class, 'File_child_id');
    }
    
}

 