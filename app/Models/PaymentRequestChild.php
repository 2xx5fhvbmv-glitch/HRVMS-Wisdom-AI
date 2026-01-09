<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRequestChild extends Model
{
    use HasFactory;

    protected $table = 'payment_request_children';
    protected $fillable = [
                            'Requested_Id',
                            'Employee_id',
                            'WorkPermitDate',
                            'WorkPermitAmt',
                            'QuotaslotDate',
                            'QuotaslotAmt',
                            'InsuranceDate',
                            'InsurancePrimume',
                            'MedicalReportDate',
                            'MedicalReportFees',
                            'VisaDate',
                            'VisaAmt',
                            'LastVisaDate','LastMedicalReportDate','LastInsuranceDate','LastQuotaslotDate','LastWorkPermitDate',
                            'WorkPermitShow',
                            'QuotaslotShow',
                            'InsuranceShow',
                            'MedicalReportShow',
                            'VisaShow',

                            'WorkPermitStep',
                            'QuotaslotStep',
                            'InsuranceStep',
                            'MedicalReportStep',
                            'VisaStep',
                            'ChildStatus',
                            'OngoingSteps',
                            'OverallSteps'
                        ];

    public function RequestedEmployees()
    {
        return $this->belongsTo(Employee::class, 'Employee_id', 'id');
    }
}
