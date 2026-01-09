<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TotalExpensessSinceJoing extends Model
{
    use HasFactory;

    public $table = 'total_expensess_since_joings';
    protected $fillable = [
                            'Date',
                            'resort_id',
                            'Deposit_Amt',              
                            'employees_id',
                            'Total_work_permit',
                            'Total_slot_Payment',
                            'Total_insurance_Payment',
                            'Total_Work_Permit_Medical_Payment',
                            'Year',
                            'Total_Visa_Payment'
                        ];
}
