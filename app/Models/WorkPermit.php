<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkPermit extends Model
{
    use HasFactory;
    public $table="work_permits";
    public $fillable = [
        'resort_id',
        'employee_id',
        'Month',
        'Currency',
        'Amt',
        'Payment_Date',
        'Due_Date',
        'Status',
        'Reciept_file',
        'Work_Permit_Number'
    ];

    public function resort()
    {
        return $this->belongsTo(Resort::class, 'resort_id');
    }
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

   
    
    
}
