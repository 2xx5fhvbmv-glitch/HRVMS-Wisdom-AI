<?php

namespace App\Http\Controllers\Resorts\Visa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\VisaEmployeeExpiryData;
use App\Helpers\Common;
use DB;
use App\Models\TotalExpensessSinceJoing;
use Carbon\Carbon;
class ExpiryController extends Controller
{

    protected $resort;
    protected $underEmp_id=[];

    public function __construct()
    {
        $this->resort = $resortId = auth()->guard('resort-admin')->user();
        if(!$this->resort) return;
        if($this->resort->is_master_admin == 0){
            if($this->resort->GetEmployee) {
                $reporting_to = $this->resort->GetEmployee->id;
                $this->underEmp_id = Common::getSubordinates($reporting_to);
            }
        }
    }

    public function index(Request $request)
    {
        if($request->ajax()) 
        {  
            $flag = $request->flag;
            $search = $request->search;
            $date   =  $request->date;
        
            if ($date)
            {
                    // Honour the full selected range "start - end". Previously this
                    // ignored the end date and forced endOfMonth() of the start,
                    // so a multi-month range only ever matched the start month.
                    $parts = array_map('trim', explode(' - ', $date));
                    $filterStart = Carbon::parse($parts[0])->startOfDay();
                    $filterEnd   = (!empty($parts[1]))
                        ? Carbon::parse($parts[1])->endOfDay()
                        : Carbon::parse($parts[0])->endOfDay();
            }
            else
            {
                $filterStart = Carbon::now()->startOfMonth();
                $filterEnd = Carbon::now()->endOfMonth();
            }
        
            $Employee = Employee::with(['resortAdmin', 'position', 'department', 'VisaRenewal.VisaChild', 'WorkPermitMedicalRenewal.WorkPermitMedicalRenewalChild', 'WorkPermit', 'EmployeeInsurance.InsuranceChild', 'QuotaSlotRenewal'])
                ->when($search, function($query) use ($search) {
                    return $query->orWhereHas('resortAdmin', function ($q) use ($search) {
                        $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                        ->orWhere('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%");
                    });
                })
                ->whereRaw('LOWER(TRIM(nationality)) != ?', ['maldivian'])
                ->where('resort_id', $this->resort->resort_id)
                ->get()
                ->map(function ($employee) use ($flag, $filterStart, $filterEnd) {
                    $employee->Emp_name        = $employee->resortAdmin->first_name .' '.$employee->resortAdmin->last_name;
                    $employee->Emp_id          = $employee->Emp_id;
                    $employee->Department_name = $employee->department->name;
                    $employee->Position_name   = $employee->position->position_title;
                    $employee->ProfilePic      = Common::getResortUserPicture($employee->resortAdmin->id);

                    // Flags
                    $hasVisaExpiry = false;
                    $hasInsuranceExpiry = false;
                    $hasWorkPermitExpiry = false;
                    $hasMedicalReportExpiry = false;
                    $hasQuotaSlotExpiry = false;

                    $employeeData = [];
                        $hasAnyFlagData = false;

                        
                            $visa = $employee->VisaRenewal;

                
                            if ($visa && Carbon::parse($visa->end_date)->between($filterStart, $filterEnd)) 
                            {
                                $employee->VisaExpiryDate = $this->getFormattedExpiryStatus($visa->end_date);
                                $employee->VisaExpiryExpiryAmt = $visa->Amt;
                                $hasVisaExpiry = true;
                            }
            

            
                            $insurance = $employee->EmployeeInsurance()->where('employee_id', $employee->id)->where('resort_id', $this->resort->resort_id)->orderBy('id', 'desc')->first();
                            if ($insurance && Carbon::parse($insurance->insurance_end_date)->between($filterStart, $filterEnd)) {
                                $employee->InsuranceExpiryDate = $this->getFormattedExpiryStatus($insurance->insurance_end_date);
                                $employee->Premium = $insurance->Premium;
                                $hasInsuranceExpiry = true;
                    
                            }
                    
                            // Work Permit expiry comes from the synced OCR data
                            // (the same "Work Permit Expiry Date (Expiry On)" field
                            // the Xpat employee details page reads), not the fee
                            // schedule's due date. Falls back to the soonest unpaid
                            // fee due date if the OCR field isn't present.
                            $wpExpiryDate = $this->getWorkPermitExpiryDate($employee->id);
                            if (!$wpExpiryDate) {
                                $fallbackWP = $employee->WorkPermit->where('Status','Unpaid')->sortByDesc('id')->first();
                                $wpExpiryDate = $fallbackWP->Due_Date ?? null;
                            }
                            if ($wpExpiryDate && Carbon::parse($wpExpiryDate)->between($filterStart, $filterEnd))
                            {
                                $employee->WorkPermitExpiryDate = $this->getFormattedExpiryStatus($wpExpiryDate);
                                $currentWP = $employee->WorkPermit->where('Status','Unpaid')->sortByDesc('id')->first();
                                $employee->WorkPermitAmt = $currentWP ? number_format($currentWP->Amt,2) : null;
                                $hasWorkPermitExpiry = true;
                            }

                            $med = $employee->WorkPermitMedicalRenewal;
                            if ($med && Carbon::parse($med->end_date)->between($filterStart, $filterEnd)) 
                            {
                                $employee->WorkPermitMedicalPermitExpiryDate = $this->getFormattedExpiryStatus($med->end_date);
                                $employee->WorkPermitMedicalPermitAmt        =  number_format($med->Amt,2);
                                $hasMedicalReportExpiry                      = true;
                            }
                    
                        
                        
                            // Filter & display both based on Due_Date so the value shown to
                            // the user matches the window being filtered.
                            $currentQuota = $employee->QuotaSlotRenewal->where('Status', 'Unpaid')->filter(fn($item) => Carbon::parse($item->Due_Date)->between($filterStart, $filterEnd))->first();
                            if ($currentQuota)
                            {
                                $employee->QuotaSlotAmtForThisMonth = $this->getFormattedExpiryStatus($currentQuota->Due_Date);
                                $employee->QuotaSlotAmtForThisMonthAmt =$currentQuota->Amt;
                                $hasQuotaSlotExpiry = true;  
                            }
                        

                    

                    
                    switch ($flag) {
                        case 'visa':
                            return $hasVisaExpiry ? $employee : null;
                        case 'insurance':
                            return $hasInsuranceExpiry ? $employee : null;
                        case 'work_permit':
                            return $hasWorkPermitExpiry ? $employee : null;
                        case 'slot_payment':
                            return $hasQuotaSlotExpiry ? $employee : null;
                        case 'medical_report':
                            return $hasMedicalReportExpiry ? $employee : null;
                        default:
                            return ($hasMedicalReportExpiry || $hasVisaExpiry || $hasInsuranceExpiry || $hasWorkPermitExpiry || $hasQuotaSlotExpiry) ? $employee : null;
                    }
                })
                ->filter();

            return datatables()->of($Employee)
                ->addColumn('profile_view', function ($row) use ($flag) {
                    $boxes = '';
                    if ($flag == 'all' || $flag == 'work_permit') {
                        $boxes .= '<div><label>Work Permit</label><p>Expires: ' . ($row->WorkPermitExpiryDate ?? '-') . '</p></div>';
                    }
                    if ($flag == 'all' || $flag == 'slot_payment') {
                        $boxes .= '<div><label>Slot Payment</label><p>Expires: ' . ($row->QuotaSlotAmtForThisMonth ?? '-') . '</p></div>';
                    }
                    if ($flag == 'all' || $flag == 'visa') {
                        $boxes .= '<div><label>Visa</label><p>Expires: ' . ($row->VisaExpiryDate ?? '-') . '</p></div>';
                    }
                    if ($flag == 'all' || $flag == 'insurance') 
                    {
                        $boxes .= '<div><label>Insurance</label><p>Expires: ' . ($row->InsuranceExpiryDate ?? '-') . '</p></div>';
                    }
                    if ($flag == 'all' || $flag == 'medical_report') 
                    {
                        $boxes .= '<div><label>Work Permit Medical</label><p>Expires: ' . ($row->WorkPermitMedicalPermitExpiryDate ?? '-') . '</p></div>';
                    }

                    return '<div class="exp-Date-userbox">
                        <div class="row align-items-lg-center">
                            <div class="col-lg-3 col-md-4">
                                <div class="user-profilebox d-flex">
                                    <div class="img-circle">
                                        <img src="' . $row->ProfilePic . '" alt="user">
                                    </div>
                                    <div>
                                        <h6>' . $row->Emp_name . ' <span class="badge badge-themeNew">#' . $row->Emp_id . '</span></h6>
                                        <p>' . ($row->Department_name . '-' . $row->Position_name) . '</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-9 col-md-8">
                                <div class="expires-date-box">' . $boxes . '</div>
                            </div>
                        </div>
                    </div>';
                })
                ->rawColumns(['profile_view'])
                ->make(true);
        }

        $page_title = 'Expiry Tracker';
        return view('resorts.Visa.expiry.index', compact('page_title'));
    }



    function getFormattedExpiryStatus($endDate)
    {
        $start = Carbon::today();
        $end = Carbon::parse($endDate);
        $daysDiff = $start->diffInDays($end, false);
        if ($daysDiff < 0) 
        {
            return $end->format('d M Y')."  (Expired " . abs($daysDiff) . " days ago)";
        }
        else
        {
            return $end->format('d M Y')."  (Expires in " . ($daysDiff ) . " days)";
        }


    }

    /**
     * Work Permit expiry date for an employee, read from the synced OCR blob
     * (VisaEmployeeExpiryData 'Other' document, field "Work Permit Expiry Date
     * (Expiry On)") — the same source the Xpat employee details page uses.
     * Returns a Y-m-d string or null.
     */
    private function getWorkPermitExpiryDate($employeeId)
    {
        $row = VisaEmployeeExpiryData::where('resort_id', $this->resort->resort_id)
            ->where('employee_id', $employeeId)
            ->where('DocumentName', 'Other')
            ->latest('id')
            ->first();
        if (!$row) {
            return null;
        }
        $data = is_array($row->Ai_extracted_data) ? $row->Ai_extracted_data : json_decode($row->Ai_extracted_data, true);
        $fields = is_array($data) ? ($data['extracted_fields'] ?? []) : [];
        $raw = $fields['Work Permit Expiry Date (Expiry On)'] ?? null;
        if (empty($raw)) {
            return null;
        }
        try {
            return Carbon::parse($raw)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }

    private function GetemployeeDocument($id)
    {

        $VisaEmployeeExpiryData = VisaEmployeeExpiryData::where('employee_id', $id)
                                                        ->where('resort_id', $this->resort->resort_id)
                                                        ->get(['created_at','DocumentName', 'Ai_extracted_data', 'File_child_id'])
                                                        ->map(function ($data)
                                                        {
                                                            $data->DocName = str_replace('_', ' ', $data->DocumentName);
                                                            $data->lastUploadedFile= $data->created_at->format('d M Y');
                                                            $data->child_id = base64_encode($data->File_child_id); 
                                                            $data->Ai_extracted_data = json_decode($data->Ai_extracted_data, true);
                                                            
                                                            return $data;
                                                        });
                                                        
        return [
                $VisaEmployeeExpiryData,
                $VisaEmployeeExpiryData->where('DocumentName','=','Other')->first(),
                $VisaEmployeeExpiryData->where('DocumentName','=','Work_Permit_Entry_Pass')->first()
            ];
    }
}
