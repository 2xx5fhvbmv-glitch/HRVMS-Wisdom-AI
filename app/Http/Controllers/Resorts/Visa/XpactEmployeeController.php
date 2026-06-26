<?php

namespace App\Http\Controllers\Resorts\Visa;

use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Helpers\Common;
use App\Helpers\StorageHelper;
use App\Models\VisaRenewal;
use App\Models\QuotaSlotRenewal;
use App\Models\EmployeeInsurance;
use App\Models\WorkPermitMedicalRenewal;
use Carbon\Carbon;
use App\Models\VisaRenewalChild;
use  App\Models\ResortBudgetCost;
use App\Models\ResortDepartment;
use App\Models\WorkPermit;
use App\Models\VisaEmployeeExpiryData;
use App\Models\ChildFileManagement;
use Storage;
use App\Models\TotalExpensessSinceJoing;
use App\Models\PaymentRequestChild;

use App\Models\PaymentRequest;
// Jobs 
use App\Jobs\UploadEmployeeFileToAws;

class XpactEmployeeController extends Controller
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

    public function XpactEmpIndex(Request $request)
    {
        $page_title = 'Xpact Employees ';
           $start = Carbon::today();

        if($request->ajax())
        {
            $searchTerm = $request->get('searchTerm');
            $status = $request->get('status');
            $departmentFilter = $request->get('departmentFilter');
            $date = $request->get('date');
           $Employee = Employee::with(['resortAdmin', 'position', 'department'])
                                ->whereNotIn('nationality', ['Maldivian'])
                                ->whereHas('resortAdmin',function ($query) {
                                    $query->where('status','Active');
                                })
                                ->where('resort_id', $this->resort->resort_id)
                                ->where('status','Active')
                                ->when(!empty($status) && $status != "All", function ($query) use ($status)
                                {
                                    $query->where('status', $status);
                                })
                                ->when(!empty($departmentFilter) && $departmentFilter != 'all', function ($query) use ($departmentFilter)
                                {
                                    $query->whereHas('department', function ($q) use ($departmentFilter) {
                                        $q->where('id', $departmentFilter);
                                    });
                                })
                                ->when(!empty($searchTerm), function ($query) use ($searchTerm) {
                                    $query->where(function ($q) use ($searchTerm) {
                                        $q->orWhere('id', $searchTerm) // if numeric ID
                                        ->orWhere('Emp_id', 'LIKE', "%$searchTerm%") // fixed Emp_id match
                                        ->orWhereHas('resortAdmin', function ($q1) use ($searchTerm) {
                                            $q1->where('first_name', 'LIKE', "%$searchTerm%")
                                                ->orWhere('last_name', 'LIKE', "%$searchTerm%")
                                                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%$searchTerm%"]);
                                        })
                                        ->orWhereHas('position', function ($q2) use ($searchTerm) {
                                            $q2->where('position_title', 'LIKE', "%$searchTerm%");
                                        })
                                        ->orWhereHas('department', function ($q3) use ($searchTerm) {
                                            $q3->where('name', 'LIKE', "%$searchTerm%");
                                        });
                                    });
                                })
                                ->when(!empty($date), function ($query) use ($date) {
                                 
                                    $query->where('joining_date', Carbon::parse($date)->format('Y-m-d'));
                                })
                                ->get()
                            ->map(function($i) use ($start) 
                            {
                                $i->profile = Common::getResortUserPicture($i->resortAdmin->id);
                                $WorkPermitMedicalRenewal = WorkPermitMedicalRenewal::where('employee_id', $i->id)->where('resort_id', $this->resort->resort_id)->first(['employee_id','Reference_Number','Cost','Currency','Medical_Center_name','start_date','end_date','medical_file']);
                                // NOTE: do NOT use $i->WorkPermit here — that name collides with
                                // the Employee::WorkPermit() hasMany relation and would resolve to
                                // an (empty) Collection that renders as "[]" in the table.
                                $i->MedicalExpiryDate = null;
                                if($WorkPermitMedicalRenewal)
                                {
                                    // getFormattedExpiryStatus already prefixes the formatted date,
                                    // so don't prepend it again (was showing "01 Jan 2027 01 Jan 2027 …").
                                    $i->MedicalExpiryDate = $this->getFormattedExpiryStatus($WorkPermitMedicalRenewal->end_date);
                                }

                                // Work Permit Expiry — MUST match the Details page's
                                // "Work Permit Fee Expiry" card, which reads the AI-extracted
                                // 'Work Permit Expiry Date (Expiry On)' field. Set below once
                                // the statistic header blob ($statisctic_emp_header) is loaded.
                                $i->WorkPermitDueDate = null;
   
                                $QuotaSlotRenewal = QuotaSlotRenewal::where('employee_id', $i->id)
                                        ->where('resort_id', $this->resort->resort_id)
                                        ->orderBy('id', 'DESC')
                                        ->where('Month',12)
                                        ->first(['employee_id', 'Month', 'Amt', 'Payment_Date', 'Due_Date', 'Currency', 'Reciept_file', 'PaymentType']);

                                if ($QuotaSlotRenewal) 
                                {
                                    $i->QuotaSlotRenewalDate =$this->getFormattedExpiryStatus($QuotaSlotRenewal->Due_Date);
                                }
                                $VisaEmployeeExpiryData1 = $this->GetemployeeDocument($i->id);
                                if ($VisaEmployeeExpiryData1) 
                                {
                                    $VisaEmployeeExpiryData = $VisaEmployeeExpiryData1[0];
                                    $statisctic_emp_header = $VisaEmployeeExpiryData1[1] ?? null;

                                    if ($statisctic_emp_header) {
                                        $insuranceExpiryRaw = $statisctic_emp_header['Ai_extracted_data']['extracted_fields']['Insurance Expiry Date'] ?? null;

                                        if ($insuranceExpiryRaw)
                                        {

                                            $i->InsuranceRenewalDate = $this->getFormattedExpiryStatus($insuranceExpiryRaw);

                                        }

                                        // Same field the Details "Work Permit Fee Expiry" card uses.
                                        $wpExpiryRaw = $statisctic_emp_header['Ai_extracted_data']['extracted_fields']['Work Permit Expiry Date (Expiry On)'] ?? null;
                                        if ($wpExpiryRaw) {
                                            $i->WorkPermitDueDate = $this->getFormattedExpiryStatus($wpExpiryRaw);
                                        }
                                    } else {
                                        $i->InsuranceRenewalDate = null;
                                    }
                                }
                                else
                                {
                                    $i->InsuranceRenewalDate = null;
                                }
                                $statisctic_emp_headerWorkPermit   =  $VisaEmployeeExpiryData1[2] ?? null;
                                if (!empty($statisctic_emp_headerWorkPermit['Ai_extracted_data']['extracted_fields'])) 
                                {
                                    $dateRaw = $statisctic_emp_headerWorkPermit['Ai_extracted_data']['extracted_fields']['Last Entry Allowed'] ?? null;

                                    if ($dateRaw) 
                                    {
                                       $i->WorkPermitPassRemingDays = $this->getFormattedExpiryStatus($dateRaw);
                                    }
                                }
                                else
                                {
                                    $i->WorkPermitPassRemingDays = null;
                                }
                                return $i;
                            });
                $edit_class = '';
                if(Common::checkRouteWisePermission('resort.visa.xpactEmployee',config('settings.resort_permissions.edit')) == false){
                    $edit_class = 'd-none';
                }
             return datatables()->of($Employee)
                ->addColumn('Profile', function ($row) {
                    $pic = $row->profile ?: asset('resorts_assets/images/user-4.svg');
                    return '<div class="img-circle"><img src="' . e($pic) . '" alt="profile" style="width:36px;height:36px;border-radius:50%;object-fit:cover;"></div>';
                })
                ->addColumn('EmployeeId', function ($row) {
                   return $row->Emp_id;
                })
                ->editColumn('EmployeeName', function ($row) {
                  return $row->resortAdmin->first_name . ' ' . $row->resortAdmin->last_name;
                })
                ->addColumn('Nationality', function ($row) {
                    return $row->nationality ?: 'N/A';
                })
                ->editColumn('position', function ($row) {
                     return $row->position->position_title ;
                })
                ->editColumn('department', function ($row) 
                {
                    return $row->department->name;
                })
                ->editColumn('JoiningDate', function ($row) 
                {
                    return date("d-m-Y",strtotime($row->joining_date))??'N/A';
                })
                
                ->editColumn('status', function ($row) {
                    if ($row->status == 'Active') 
                    {
                        $statusClass = 'badge badge-success';
                        $activeStatus = 'Active';
                    }
                    elseif($row->status == 'InActive')
                    {
                        $activeStatus = 'In Active';
                        $statusClass = 'badge badge-infoBorder';
                    }
                    else
                    {
                        $activeStatus = $row->status;
                        $statusClass = 'badge badge-secondary';
                    }
                    return '<span class="' . $statusClass . '">' . $activeStatus . '</span>';
                })
                ->editColumn('Insurance', function ($row) 
                {
                    return $row->InsuranceRenewalDate ?? 'N/A';
                })
                 ->editColumn('WorkPermitDue', function ($row)
                {
                    return $row->WorkPermitDueDate ?? 'N/A';
                })
                ->addColumn('MedicalExpiry', function ($row)
                {
                    return $row->MedicalExpiryDate ?? 'N/A';
                })
                ->editColumn('SlotPaymentDue', function ($row)
                {
                    return $row->QuotaSlotRenewalDate ?? 'N/A';
                })
                ->editColumn('action', function ($row) use ($edit_class)
                {
                    return '<a target="_blank" href="' . route('resort.visa.XpactEmpDetails', base64_encode($row->id)) . '" class="btn btn-themeSkyblue btn-sm ' . $edit_class . '">Edit</a>';
                })
                ->rawColumns(['Profile','EmployeeId','Nationality','position','department', 'JoiningDate','Insurance','WorkPermitDue','MedicalExpiry', 'SlotPaymentDue', 'status', 'action'])
                ->make(true);
        }


            
        $departments = ResortDepartment::where('resort_id', $this->resort->resort_id)->get();
              
        return view('resorts.Visa.employee.xpactemp', compact('page_title','departments'));
    }

    public function XpactEmpDetails($id)
    { 
        if(Common::checkRouteWisePermission('resort.visa.xpactEmployee',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized action.');
        }
        $id = base64_decode($id);
        $page_title = "Xpat Employee Details";
        $Employee = Employee::with(['resortAdmin', 'position', 'department'])->where("id",$id)->where("nationality",'!=',"Maldivian")->where('resort_id', $this->resort->resort_id)->first();
        // Guard: a bad id, another resort's employee, or a Maldivian (excluded
        // above) yields null — without this the page 500s on the next line.
        if (!$Employee) {
            return redirect()->route('resort.visa.xpactEmployee')->with('error', 'Employee not found.');
        }

        $Employee->profilePic = Common::getResortUserPicture($Employee->resortAdmin->id);

        $start = Carbon::now();
        $QuotaSlotRenewal = QuotaSlotRenewal::where('employee_id', $id)->where('resort_id', $this->resort->resort_id)->orderBy('id', 'DESC')->first(['employee_id', 'Month', 'Amt', 'Payment_Date', 'Due_Date', 'Currency', 'Reciept_file','PaymentType']);

        $QuotaSlotDeposit = ResortBudgetCost::whereIn('particulars',['QUOTA SLOT DEPOSIT','Quota Slot Deposit','quota slot deposit'])->where("details","Xpat Only")->where('status','active')->where('resort_id',$this->resort->resort_id)->orderBy('updated_at', 'DESC')->first(['particulars','amount','amount_unit']);
        $WorkPermitFee = ResortBudgetCost::whereIn('particulars',['Work Permit fee','work permit fee','WORK PERMIT FEE','QUOTA SLOT DEPOSIT','Quota Slot Deposit','quota slot deposit'])->where("details","Xpat Only")->where('status','active')->where('resort_id',$this->resort->resort_id)->orderBy('updated_at', 'DESC')->first(['particulars','amount','amount_unit']);
        $DepositPayment = ResortBudgetCost::whereIn('particulars',['Deposit Payment','DEPOSIT PAYMENT','deposit payment'])->where("details","Xpat Only")->where('status','active')->where('resort_id',$this->resort->resort_id)->orderBy('updated_at', 'DESC')->first(['particulars','amount','amount_unit']);
        $TotalInsuranePremium = ResortBudgetCost::whereIn('particulars',['Deposit Payment','DEPOSIT PAYMENT','deposit payment'])->where("details","Xpat Only")->where('status','active')->where('resort_id',$this->resort->resort_id)->orderBy('updated_at', 'DESC')->first(['particulars','amount','amount_unit']);
  
        $TotalInsuranePremium    =  ResortBudgetCost::whereIn('particulars',['Deposit Payment','DEPOSIT PAYMENT','deposit payment'])->where("details","Xpat Only")->where('status','active')->where('resort_id',$this->resort->resort_id)->orderBy('updated_at', 'DESC')->first(['particulars','amount','amount_unit']);
        $VisaEmployeeExpiryData1 =  $this->GetemployeeDocument($id);

        $VisaEmployeeExpiryData  =  $VisaEmployeeExpiryData1[0];
        $statisctic_emp_header   =  $VisaEmployeeExpiryData1[1] ?? null;
        $statisctic_emp_headerWorkPermit   =  $VisaEmployeeExpiryData1[2] ?? null;
        if($statisctic_emp_header)
        {
            $Ai_extracted_data = $statisctic_emp_header->Ai_extracted_data;
            // Read fields null-safely — the OCR blob (or a manually-submitted one
            // from verify-details) may not contain every key, so accessing them
            // directly threw "Undefined array key …" on the details page.
            $fields = (is_array($Ai_extracted_data) ? ($Ai_extracted_data['extracted_fields'] ?? []) : []);

            // Visa Expiry Date -> previously rendered "Visa Issued Date" as the
            // visa-expiry value, which made the visible date wrong. Use the
            // dedicated Visa Expiry Date field (same field already feeds the
            // "remaining days" tag below).
            if(!empty($fields['Visa Expiry Date']))
            {
                $statisctic_emp_header->Name ="Visa Expiry";
                $statisctic_emp_header->VisaExpiryDate = $this->safeDate($fields['Visa Expiry Date']);
                $statisctic_emp_header->VisaRemingDays = $this->getFormattedExpiryStatus($fields['Visa Expiry Date']);
            }
             // Insurance Expiry Date ->
            if(!empty($fields['Insurance Expiry Date']))
            {
                $statisctic_emp_header->Name ="Insurance Expiry";
                $statisctic_emp_header->InsuranceExpiryDate = $this->safeDate($fields['Insurance Expiry Date']);
                $statisctic_emp_header->InsuranceRemingDays = $this->getFormattedExpiryStatus($fields['Insurance Expiry Date']);
                $statisctic_emp_header->QuotaSlotNumber = $fields['Quota Slot Number'] ?? null;
            }
            // Work permit Expiry
            if(!empty($fields['Work Permit Expiry Date (Expiry On)']))
            {
                $statisctic_emp_header->Name ="Work Permit Expiry";
                $statisctic_emp_header->WorkPermitExpiryDate = $this->safeDate($fields['Work Permit Expiry Date (Expiry On)']);
                $statisctic_emp_header->WorkPermitRemingDays = $this->getFormattedExpiryStatus($fields['Work Permit Expiry Date (Expiry On)']);
            }
        }
        // Work Permit Card Expiry
        if(!empty($statisctic_emp_headerWorkPermit['Ai_extracted_data']['extracted_fields']['Last Entry Allowed']))
        {
            $date = $statisctic_emp_headerWorkPermit['Ai_extracted_data']['extracted_fields']['Last Entry Allowed'];
            $statisctic_emp_header->Name = "Work Permit Entry Pass";
            $statisctic_emp_header->LastEntryDate = $this->safeDate($date);
            $statisctic_emp_header->WorkPermitPassRemingDays = $this->getFormattedExpiryStatus($date);
        }
        if($QuotaSlotRenewal)
        {
            $QuotaSlotRenewal->QuotaslotExpiryDate = $this->safeDate($QuotaSlotRenewal->Due_Date);
            $QuotaSlotRenewal->QuotaslotRemingDays = $this->getFormattedExpiryStatus($QuotaSlotRenewal->Due_Date);
        }

        // Passport Expiry — from the OCR-extracted Passport_Copy document.
        // The expiry lives in Ai_extracted_data->extracted_fields->"Date of Expiry"
        // (format like "12Mar2026"). Was hardcoded "15 Mar 2026" in the view.
        $passportExpiryDate = null;
        $passportExpiryStatus = null;
        $passportRow = VisaEmployeeExpiryData::where('resort_id', $this->resort->resort_id)
            ->where('employee_id', $id)
            ->where('DocumentName', 'Passport_Copy')
            ->latest('id')
            ->first();
        if ($passportRow) {
            // Ai_extracted_data is stored as a JSON STRING (the model has no array
            // cast), so decode before reading — array-indexing the raw string just
            // yields a character and the passport date showed as N/A even after a
            // manual update wrote the correct value.
            $decodedPassport = is_array($passportRow->Ai_extracted_data)
                ? $passportRow->Ai_extracted_data
                : (json_decode($passportRow->Ai_extracted_data, true) ?: []);
            $extracted = $decodedPassport['extracted_fields'] ?? [];
            $rawPassportExpiry = $extracted['Date of Expiry'] ?? ($extracted['Passport Expiry Date'] ?? null);
            if (!empty($rawPassportExpiry)) {
                try {
                    $parsed = Carbon::parse($rawPassportExpiry);
                    $passportExpiryDate = $parsed->format('d M Y');
                    $passportExpiryStatus = $this->getFormattedExpiryStatus($parsed->toDateString());
                } catch (\Exception $e) {
                    $passportExpiryDate = null;
                }
            }
        }

        // Medical Expiry — from the employee's Work Permit Medical Renewal record
        // (same source as the Medical Expiry column on the xpat-employee list).
        $medicalExpiryDate = null;
        $medicalExpiryStatus = null;
        $wpMedical = WorkPermitMedicalRenewal::where('resort_id', $this->resort->resort_id)
            ->where('employee_id', $id)->orderBy('id', 'desc')->first(['end_date']);
        if ($wpMedical && $wpMedical->end_date) {
            $medicalExpiryDate   = Carbon::parse($wpMedical->end_date)->format('d M Y');
            $medicalExpiryStatus = $this->getFormattedExpiryStatus($wpMedical->end_date);
        }

        $TotalExpensessSinceJoing = $this->TotalExpensessSinceJoing($id);

        return view('resorts.Visa.employee.XpatEmployeeDetails', compact('page_title','Employee','statisctic_emp_header','QuotaSlotRenewal','VisaEmployeeExpiryData','TotalExpensessSinceJoing','passportExpiryDate','passportExpiryStatus','medicalExpiryDate','medicalExpiryStatus'));
    }

    /**
     * Manual override of an employee's visa expiry dates from the details page
     * (used when the AI sync couldn't read a field). Writes each date into the
     * OCR blob the details cards read, creating the blob row if needed. Only
     * filled fields are updated — blank inputs keep the current value.
     */
    public function UpdateXpatDetails(Request $request)
    {
        $request->validate([
            'employee_id'        => 'required',
            'visa_expiry'        => 'nullable|date',
            'insurance_expiry'   => 'nullable|date',
            'passport_expiry'    => 'nullable|date',
            'medical_expiry'     => 'nullable|date',
            'work_permit_start'  => 'nullable|date',
            'work_permit_end'    => 'nullable|date|after_or_equal:work_permit_start',
            'slot_start'         => 'nullable|date',
            'slot_end'           => 'nullable|date|after_or_equal:slot_start',
        ]);

        $eid = (int) base64_decode($request->employee_id);
        $rid = $this->resort->resort_id;
        $employee = Employee::where('id', $eid)->where('resort_id', $rid)->first();
        if (!$employee) {
            return response()->json(['status' => false, 'message' => 'Employee not found.'], 404);
        }

        // Upsert one extracted field into a given OCR document blob.
        $upsert = function (string $docName, string $field, $value) use ($eid, $rid) {
            if (empty($value)) {
                return;
            }
            $value = \Carbon\Carbon::parse($value)->format('Y-m-d');
            $row = VisaEmployeeExpiryData::where('employee_id', $eid)->where('resort_id', $rid)
                ->where('DocumentName', $docName)->orderBy('id', 'desc')->first();
            $payload = $row ? (json_decode($row->Ai_extracted_data, true) ?: []) : [];
            $fields = $payload['extracted_fields'] ?? [];
            $fields[$field] = $value;
            $payload['extracted_fields'] = $fields;
            if ($row) {
                $row->Ai_extracted_data = json_encode($payload);
                $row->save();
            } else {
                VisaEmployeeExpiryData::create([
                    'resort_id' => $rid, 'employee_id' => $eid, 'File_child_id' => null,
                    'Ai_extracted_data' => json_encode($payload), 'DocumentName' => $docName,
                ]);
            }
        };

        $upsert('Other', 'Visa Expiry Date', $request->visa_expiry);
        $upsert('Other', 'Insurance Expiry Date', $request->insurance_expiry);
        $upsert('Passport_Copy', 'Date of Expiry', $request->passport_expiry);
        // Work Permit card still reads the OCR display date — keep it in sync with
        // the schedule's end month.
        $upsert('Other', 'Work Permit Expiry Date (Expiry On)', $request->work_permit_end);

        // Medical Expiry — write to the real Work Permit Medical Renewal record
        // (the Medical card reads end_date from this table). Update the latest row
        // or create one so the card reflects the manual entry.
        if (!empty($request->medical_expiry)) {
            $medEnd = \Carbon\Carbon::parse($request->medical_expiry)->format('Y-m-d');
            $med = WorkPermitMedicalRenewal::where('employee_id', $eid)->where('resort_id', $rid)
                ->orderBy('id', 'desc')->first();
            if ($med) {
                $med->end_date = $medEnd;
                $med->save();
            } else {
                WorkPermitMedicalRenewal::create([
                    'resort_id' => $rid, 'employee_id' => $eid,
                    'end_date'  => $medEnd, 'Currency' => 'MVR', 'Status' => 'Unpaid',
                ]);
            }
        }

        // Resolve a per-month fee in MVR, respecting the budget row's amount_unit:
        // a row stored in $/USD is converted up to MVR; a row already in MVR is used
        // as-is (converting it again would multiply an MVR amount by the FX rate).
        $feeFor = function (array $particulars) use ($rid) {
            $b = ResortBudgetCost::whereIn('particulars', $particulars)
                ->where('details', 'Xpat Only')->where('status', 'active')->where('resort_id', $rid)
                ->orderBy('updated_at', 'DESC')->first(['amount', 'amount_unit']);
            if (!$b) {
                return 0.0;
            }
            $amt  = (float) $b->amount;
            $unit = strtoupper(trim((string) $b->amount_unit));
            if ($unit === '$' || $unit === 'USD' || $unit === 'DOLLAR') {
                return round((float) Common::RateConversion('DollerToMVR', $amt, $rid), 2);
            }
            return round($amt, 2); // already MVR
        };

        // Visa Expiry — create/update the VisaRenewal record so the Visa expiry shows
        // in the dashboard / expiry overview (which read records, not the OCR blob),
        // mirroring what the Xpat sync writes.
        if (!empty($request->visa_expiry)) {
            $visaEnd = \Carbon\Carbon::parse($request->visa_expiry)->format('Y-m-d');
            $visaFee = $feeFor(['VISA FEE', 'Visa Fee', 'visa fee']);
            $visa = VisaRenewal::where('employee_id', $eid)->where('resort_id', $rid)->orderBy('id', 'desc')->first();
            if ($visa) {
                $visa->end_date = $visaEnd;
                if ((float) $visa->Amt <= 0) { $visa->Amt = $visaFee; }
                $visa->save();
            } else {
                VisaRenewal::create([
                    'resort_id' => $rid, 'employee_id' => $eid,
                    'end_date'  => $visaEnd, 'Amt' => $visaFee, 'Currency' => 'MVR', 'Status' => 'Pending',
                ]);
            }
        }

        // Insurance Expiry — create/update the EmployeeInsurance record.
        if (!empty($request->insurance_expiry)) {
            $insEnd = \Carbon\Carbon::parse($request->insurance_expiry)->format('Y-m-d');
            $insFee = $feeFor(['MEDICAL INSURANCE - INTERNATIONAL', 'Medical Insurance - International', 'medical insurance - international']);
            $ins = EmployeeInsurance::where('employee_id', $eid)->where('resort_id', $rid)->orderBy('id', 'desc')->first();
            if ($ins) {
                $ins->insurance_end_date = $insEnd;
                if ((float) $ins->Premium <= 0) { $ins->Premium = $insFee; }
                $ins->save();
            } else {
                EmployeeInsurance::create([
                    'resort_id' => $rid, 'employee_id' => $eid,
                    'insurance_end_date' => $insEnd, 'Premium' => $insFee, 'Currency' => 'MVR', 'Status' => 'Unpaid',
                ]);
            }
        }

        // Generate one monthly fee installment per month from $start to $end
        // (inclusive) for the given model. Idempotent — a month that already has an
        // installment (same Due_Date) is skipped, so re-saving the same range won't
        // duplicate rows. This is what fills the (otherwise empty) Work Permit / Quota
        // Slot payment schedules for employees not created via the Xpact sync.
        $generateMonthly = function (string $modelClass, $start, $end, float $fee) use ($eid, $rid) {
            $cursor = \Carbon\Carbon::parse($start)->startOfDay();
            $stop   = \Carbon\Carbon::parse($end)->startOfDay();
            $guard  = 0; // hard cap to avoid runaway loops on bad input
            while ($cursor->lte($stop) && $guard < 120) {
                $due = $cursor->toDateString();
                $exists = $modelClass::where('employee_id', $eid)->where('resort_id', $rid)
                    ->whereDate('Due_Date', $due)->exists();
                if (!$exists) {
                    $modelClass::create([
                        'resort_id'   => $rid,
                        'employee_id' => $eid,
                        'Month'       => $cursor->month,
                        'Amt'         => $fee,
                        'Currency'    => 'MVR',
                        'Due_Date'    => $due,
                        'Status'      => 'Unpaid',
                    ]);
                }
                $cursor->addMonthNoOverflow();
                $guard++;
            }
        };

        // Work Permit Fee schedule (monthly, configured Work Permit fee).
        if (!empty($request->work_permit_start) && !empty($request->work_permit_end)) {
            $generateMonthly(WorkPermit::class, $request->work_permit_start, $request->work_permit_end,
                $feeFor(['Work Permit fee', 'work permit fee', 'WORK PERMIT FEE']));
        }

        // Quota Slot Fee schedule (monthly, configured Quota Slot fee).
        if (!empty($request->slot_start) && !empty($request->slot_end)) {
            $generateMonthly(QuotaSlotRenewal::class, $request->slot_start, $request->slot_end,
                $feeFor(['QUOTA SLOT DEPOSIT', 'Quota Slot Deposit', 'quota slot deposit']));
        }

        return response()->json(['status' => true, 'message' => 'Details updated successfully.']);
    }

    public function XpactEmpBudgetCost(Request $request)
    {

        $ResortBudgetCost = ResortBudgetCost::whereIn('particulars',['QUOTA SLOT DEPOSIT','Quota Slot Deposit','quota slot deposit'])->where("details","Xpat Only")->where('status','active')->where('resort_id',$this->resort->resort_id)->orderBy('updated_at', 'DESC')->first(['particulars','amount','amount_unit']);
        // Guard against missing budget-cost row for this resort — avoid null read breaking the DataTable
        $BudgetAmt = $ResortBudgetCost ? (float) $ResortBudgetCost->amount : 0;
        $PayableAmt = Common::RateConversion("DollerToMVR", $BudgetAmt, $this->resort->resort_id);


        if($request->ajax())
        {
             $employee_id = base64_decode($request->employee_id);
          

            if($request->flag =="Quota_Slot_Fee")
            {
                $QuotaSlotRenewalPaid =  QuotaSlotRenewal::where('employee_id', $employee_id)->where('resort_id', $this->resort->resort_id)->orderBy('Month', 'ASC')
                                                        ->get();
                $PaidAmt = $QuotaSlotRenewalPaid->where("Status","Paid")->sum('Amt');
                $UnPaidAmt = $QuotaSlotRenewalPaid->where("Status","Unpaid")->sum('Amt');

                // Show ALL installments (paid + pending), chronologically — the
                // schedule previously hid paid rows (Status=Unpaid filter), which
                // dropped the paid months (e.g. the paid August installment).
                $CommonVariable = QuotaSlotRenewal::where('employee_id', $employee_id)->where('resort_id', $this->resort->resort_id)->orderBy('Due_Date', 'ASC')
                                ->get()
                                ->map(function($i)
                                {
                                    $i->type="QuotaSlot";
                                    return $i;
                                });
                $PayableAmt = $PaidAmt + $UnPaidAmt;
                

            }
            else
            {
                $WorkPermit =  WorkPermit::where('employee_id', $employee_id)->where('resort_id', $this->resort->resort_id)->orderBy('Month', 'ASC')
                    ->get();

                    $PaidAmt = $WorkPermit->where("Status","Paid")->sum('Amt');
                    $UnPaidAmt = $WorkPermit->where("Status","Unpaid")->sum('Amt');

                    $CommonVariable = WorkPermit::where('employee_id', $employee_id)->where('resort_id', $this->resort->resort_id)->orderBy('Due_Date', 'ASC')
                                    ->get()
                                     ->map(function($i)
                                    {
                                        $i->type="WorkPermit";
                                        return $i;
                                    });
                    $PayableAmt = $PaidAmt + $UnPaidAmt;

            }
          
            return datatables()->of($CommonVariable) 
                ->addColumn('Month', function ($row) {
                   return Carbon::parse($row->Due_Date)->format('F Y');
                })
                ->editColumn('Amount', function ($row) {
                  return $row->Amt. ' ' . $row->Currency;
                })
                ->editColumn('DueDate', function ($row) {
                     return Carbon::parse($row->Due_Date)->format('d M Y');
                })
                ->editColumn('Status', function ($row) 
                {
                    if($row->Status =="Paid")
                    {
                        return '<span class="badge badge-themeSuccess">Paid</span>';
                    }
                    else
                    {
                        return '<span class="badge badge-themeDanger">Pending</span>';
                    }
                  
                })
                ->editColumn('PaymentDate', function ($row)
                {
                    // Field is Payment_Date (underscore); reading ->PaymentDate was
                    // always null, so the column always showed '-'.
                    return !empty($row->Payment_Date)
                        ? Carbon::parse($row->Payment_Date)->format('d M Y')
                        : '-';
                })
                ->editColumn('Action', function ($row) 
                {
                        if($row->Status !="Paid")
                        {
                            return '<a target="_blank" data-id="'.base64_encode($row->id).'" data-type="'.$row->type.'"  class="a-link markasPaid">Mark as Paid</a>';
                        }
                })
                 ->editColumn('created_at', function ($row) 
                {
                        
                            return $row->created_at;
                        
                })
                 ->with([
                    'footerData' => 
                    [
                        'PayableAmount' => 'MVR ' . number_format($PayableAmt, 2),
                        'AmountPaid' => 'MVR ' . number_format($PaidAmt, 2),
                        'BalanceAmount' => 'MVR ' . number_format($UnPaidAmt, 2),
                    ]
                ])
                ->rawColumns(['Month','Amount','DueDate', 'Status','PaymentDate', 'Action'])
                ->make(true);


        }
    }
    public function QuotaSlotMakrasPaid(Request $request)
    {
        $child_id = base64_decode($request->child_id);
        $child    = $child_id ? PaymentRequestChild::find($child_id) : null;

        // Who is settling this — the logged-in (Finance) user — so the details
        // page can show "Renewed by …".
        $payer     = $this->resort;
        $payerName = $payer ? trim(($payer->first_name ?? '') . ' ' . ($payer->last_name ?? '')) : '';
        if ($payerName === '') {
            $payerName = optional($payer)->email ?: 'Finance';
        }

        // Notify HR when Finance completes a payment (HR raises the request, so
        // they should know it's been settled).
        $notifyHr = function ($employeeId, string $feeLabel, $amount) use ($payerName) {
            $hrIds = Common::getResortHrEmployeeIds($this->resort->resort_id);
            if (empty($hrIds)) {
                return;
            }
            $emp = Employee::with('resortAdmin')->find($employeeId);
            $empName = ($emp && $emp->resortAdmin)
                ? trim($emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name)
                : ('Employee #' . $employeeId);
            $title   = 'Visa Payment Completed';
            $message = $feeLabel . ' for ' . $empName . ' (MVR ' . number_format((float) $amount, 2) . ') has been marked Paid by ' . $payerName . '.';
            foreach ($hrIds as $hrId) {
                try {
                    event(new \App\Events\ResortNotificationEvent(Common::nofitication(
                        $this->resort->resort_id, 10, $title, $message, 0, (int) $hrId, 'Visa'
                    )));
                } catch (\Throwable $e) {
                    \Log::warning('Mark-paid HR notify failed (resort ' . $this->resort->resort_id . ', hr ' . $hrId . '): ' . $e->getMessage());
                }
            }
        };

        // Advance the payment-request child one step (a fee type is one step,
        // regardless of how many months it covered) and complete it when done.
        $advanceChild = function ($child, string $showField, string $stepField) {
            if (!$child) {
                return;
            }
            $child->OngoingSteps = $child->OngoingSteps + 1;
            if ($child->OverallSteps == $child->OngoingSteps) {
                $child->ChildStatus = 'Complete';
            }
            $child->{$showField} = 'No';
            $child->{$stepField} = 'Yes';
            $child->save();

            // Mark the WHOLE request Paid (Approved) only once EVERY child/employee
            // in it is complete — not when just one employee's fees are settled.
            $hasIncomplete = PaymentRequestChild::where('Requested_Id', $child->Requested_Id)
                ->where(function ($q) {
                    $q->whereNull('ChildStatus')->orWhere('ChildStatus', '!=', 'Complete');
                })->exists();
            if (!$hasIncomplete) {
                PaymentRequest::where('id', $child->Requested_Id)->update(['Status' => 'Approved']);
            }
        };

        if($request->TypeofModel =="WorkPermit")
        {
            // Settle every month included in this request at once: the stored
            // multi-month ids, else the single Mark_id for legacy requests.
            $ids = ($child && !empty($child->WorkPermitIds))
                ? array_filter(array_map('intval', explode(',', $child->WorkPermitIds)))
                : [ (int) base64_decode($request->Mark_id) ];

            $rows = WorkPermit::whereIn('id', $ids)->where('resort_id', $this->resort->resort_id)->get();
            if ($rows->isEmpty()) {
                return response()->json(['status' => false, 'message' => 'Work Permit Slot Renewal not found']);
            }

            $paidSum = 0.0;
            foreach ($rows as $WorkPermit) {
                if (strtolower((string) $WorkPermit->Status) === 'paid') {
                    continue;
                }
                $WorkPermit->Status        = "Paid";
                $WorkPermit->ReceiptNumber = $request->Receipt_number;
                $WorkPermit->Payment_Date  = Carbon::now()->format('Y-m-d');
                $WorkPermit->paid_by       = $payerName;
                $WorkPermit->save();
                $paidSum += (float) $WorkPermit->Amt;
            }

            $TotalExpensessSinceJoing = TotalExpensessSinceJoing::where('resort_id', $this->resort->resort_id)->where('employees_id', $rows->first()->employee_id)->first();
            if ($TotalExpensessSinceJoing) {
                $TotalExpensessSinceJoing->Total_work_permit = $TotalExpensessSinceJoing->Total_work_permit + $paidSum;
                $TotalExpensessSinceJoing->save();
            }

            $notifyHr($rows->first()->employee_id, 'Work Permit fee', $paidSum);
            $advanceChild($child, 'WorkPermitShow', 'WorkPermitStep');
            return response()->json(['status' => true, 'message' => 'Marked as Paid successfully']);
        }
        else
        {
            $ids = ($child && !empty($child->QuotaslotIds))
                ? array_filter(array_map('intval', explode(',', $child->QuotaslotIds)))
                : [ (int) base64_decode($request->Mark_id) ];

            $rows = QuotaSlotRenewal::whereIn('id', $ids)->where('resort_id', $this->resort->resort_id)->get();
            if ($rows->isEmpty()) {
                return response()->json(['status' => false, 'message' => 'Quota Slot Renewal not found']);
            }

            $paidSum = 0.0;
            foreach ($rows as $QuotaSlotRenewal) {
                if (strtolower((string) $QuotaSlotRenewal->Status) === 'paid') {
                    continue;
                }
                $QuotaSlotRenewal->Status        = "Paid";
                $QuotaSlotRenewal->ReceiptNumber = $request->Receipt_number;
                $QuotaSlotRenewal->Payment_Date  = Carbon::now()->format('Y-m-d');
                $QuotaSlotRenewal->paid_by       = $payerName;
                $QuotaSlotRenewal->save();
                $paidSum += (float) $QuotaSlotRenewal->Amt;
            }

            $TotalExpensessSinceJoing = TotalExpensessSinceJoing::where('resort_id', $this->resort->resort_id)->where('employees_id', $rows->first()->employee_id)->first();
            if ($TotalExpensessSinceJoing) {
                $TotalExpensessSinceJoing->Total_slot_Payment = $TotalExpensessSinceJoing->Total_slot_Payment + $paidSum;
                $TotalExpensessSinceJoing->save();
            }

            $notifyHr($rows->first()->employee_id, 'Slot fee', $paidSum);
            $advanceChild($child, 'QuotaslotShow', 'QuotaslotStep');
            return response()->json(['status' => true, 'message' => 'Marked as Paid successfully']);
        }

    }
    public function PastTransectionHistory(Request $request)
    {
        if($request->ajax())
        {
            $employee_id = base64_decode($request->employee_id);      
            $SelectYear = $request->SelectYear;
            $quotaSlotData = QuotaSlotRenewal::where('employee_id', $employee_id)
                                ->where('resort_id', $this->resort->resort_id);
                            
            if ($SelectYear != "ALL" && isset($SelectYear) && !empty($SelectYear)) {
                $quotaSlotData->whereYear('Due_Date', $SelectYear);
            }
            $quotaSlotData = $quotaSlotData->where('Status', 'Paid')
                            ->orderBy('id', 'DESC')
                            ->get([
                                'id', 'employee_id', 'Month', 'Amt', 'Payment_Date',
                                'Due_Date', 'Currency', 'Reciept_file', 'PaymentType', 'ReceiptNumber',
                                'created_at'
                            ])
                            ->map(function($item) {
                                $item->transaction_type = 'Quota Slot';
                                return $item;
                            });
            // Get WorkPermit data
            $workPermitData = WorkPermit::where('employee_id', $employee_id)->where('resort_id', $this->resort->resort_id);

            if ($SelectYear != "ALL" && isset($SelectYear) && !empty($SelectYear)) 
            {
                $workPermitData->whereYear('Due_Date', $SelectYear);
            }
            
            $workPermitData = $workPermitData->where('Status', 'Paid')
                            ->orderBy('id', 'DESC')
                            ->get([
                                'id', 'employee_id', 'Month', 'Amt', 'Payment_Date',
                                'Due_Date', 'Currency', 'Reciept_file', 'PaymentType', 'ReceiptNumber', 'created_at'
                            ])
                            ->map(function($item) {
                                $item->transaction_type = 'Work Permit';
                                return $item;
                            });
                            
            // Combine both collections and sort by Due_Date
            $MixDatas = $quotaSlotData->concat($workPermitData)
                                        ->sortByDesc('Due_Date')
                                        ->values();
            return datatables()->of($MixDatas)
                ->editColumn('Year', function ($row) 
                {
                  return  Carbon::parse($row->Due_Date)->format('Y');
                })
                ->editColumn('TransactionType', function ($row) {
                  // Work Permit should not show the "(Installment)" payment-type bracket.
                  $label = '<b>'.$row->transaction_type.'</b>';
                  if ($row->transaction_type !== 'Work Permit' && !empty($row->PaymentType)) {
                      $label .= ' ('.$row->PaymentType.')';
                  }
                  return $label;
                })
                ->editColumn('Amount', function ($row) {
                  return $row->Amt. ' ' . $row->Currency;
                })
                  ->addColumn('Date', function ($row) {
                   return Carbon::parse($row->Due_Date)->format('F Y');
                })
                ->editColumn('ReceiptNo', function ($row) {
                     return $row->ReceiptNumber ?: '-';
                })
                ->editColumn('Status', function ($row) 
                {
                    return '<span class="badge badge-themeSuccess">Paid</span>';
                })
              
               
                ->rawColumns(['Year','TransactionType','Amount', 'Date','ReceiptNo', 'Status'])
                ->make(true);
        }
    }
    public function EmployeeWiseVisaDocumentUpload(Request $request)
    {
        $DocumentType = $request->DocumentType;
        $employee_id  = base64_decode($request->emp_id);
        $flag ='';
        if($DocumentType == "Insurance")
        {
            $flag="insurance";
        }
        elseif($DocumentType == "Work_Permit_Entry_Pass")
        {
            $flag="work_permit_entry_pass";
        }
        elseif($DocumentType == "Work_Permit_Card")
        {
            $flag="work_permit_card";
        }
        elseif($DocumentType == "Visa")
        {
            $flag="visa";
        }
        elseif($DocumentType == "Medical_Report")
        {
            $flag="medical_report";
        }
        elseif($DocumentType == "Passport_Copy") 
        {
            $flag="passport";
        }

        $main_folder = $this->resort->resort->resort_id;
        $file         = $request->file('DocumentFile');

        $employee    = Employee::where('resort_id',$this->resort->resort_id)->where("id",$employee_id)->first();
       
        $aws =  Common::AWSEmployeeFileUpload($this->resort->resort_id,$file,$employee->Emp_id); 
        
        if($aws['status'] == true)
        {
            $file_child_id  = $aws['Chil_file_id'];
            $url = env('AI_extract_work_details_URL').$flag; 
                $curl = curl_init();
                $postFields = [
                    'file' => new \CURLFile($file->getRealPath(), $file->getMimeType(), $file->getClientOriginalName()),
                    'doc_type' => $flag,
                ];
                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $postFields,
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                    ],
                    // Hostinger reverse proxy kills the request at ~60 s.
                    // 50 s timeout here keeps the failure inside PHP so
                    // the user sees a clean JSON error instead of a
                    // generic HTML "Request Timeout" page.
                    CURLOPT_TIMEOUT => 50,
                    CURLOPT_CONNECTTIMEOUT => 10,
                ]);
                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);
                if($err)
                {
                    return response()->json(['status' => false, 'message' =>  $err]);
                } 
                $AI_Data = json_decode($response, true); 
            
                DB::beginTransaction();
                try
                {
                    if($DocumentType == "Visa")
                    {
                        $VisaRenewal =  VisaRenewal::where('resort_id', $this->resort->resort_id)
                                    ->where('employee_id', $employee->id)
                                    ->update([  
                                                'visa_file'=>$file_child_id,
                                                'resort_id' => $this->resort->resort_id,
                                                'employee_id' => $employee->id,
                                                'Visa_Number'=> $AI_Data['extracted_fields']['Visa No.'] ?? null,
                                                'WP_No'=> $AI_Data['extracted_fields']['Work Permit Number (Starts with WP)'] ?? null,
                                                'start_date' =>  Carbon::createFromFormat('d/m/Y', $AI_Data['extracted_fields']['Visa Issued Date']),
                                                'end_date' =>Carbon::createFromFormat('d/m/Y', $AI_Data['extracted_fields']['Visa Expiry Date']),
                                            ]);
                    }
                    elseif($DocumentType == "Work_Permit_Card")
                    {

                            WorkPermit::where('resort_id', $this->resort->resort_id)
                            ->where('employee_id', $employee->id)
                            ->update([
                                'Reciept_file'=> $file_child_id,
                                'Work_Permit_Number' => $AI_Data['extracted_fields']['Work Permit Number'] ?? null,
                            ]);
                    }
                        VisaEmployeeExpiryData::where('resort_id', $this->resort->resort_id)
                        ->where('employee_id', $employee->id)
                        ->where('DocumentName', $DocumentType)
                        ->delete();
                        VisaEmployeeExpiryData::create(['resort_id' => $this->resort->resort_id,
                            'employee_id' => $employee->id,
                            'File_child_id' => $file_child_id ?? null,
                            'Ai_extracted_data' => $response ?? null,
                            'DocumentName' => $DocumentType ?? null
                        ]);
                    DB::commit();
                    $GetemployeeDocument = $this->GetemployeeDocument($employee->id);
                    $GetemployeeDocument = $GetemployeeDocument[0];
                    return response()->json(['status' => true, 'data'=>$GetemployeeDocument,'message' => 'File uploaded successfully.']);
                }
                catch(\Exception $e)
                {
                    DB::rollBack();
                    return response()->json(['status' => false, 'message' => 'Error occurred while processing the file.']);
                }
      
        }
        else
        {
            if($aws['msg'])
            {

                return response()->json(['status' => false, 'message' => $aws['msg']]);
            }
            else
            {
                
                return response()->json(['status' => false, 'message' => 'File upload failed. Please try again.']);
            }
         
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
    public function XpactEmpFileDownload($id)
    {
     
        $ChildFiles = ChildFileManagement::where("id",base64_decode($id))->where("resort_id"   ,$this->resort->resort_id)->first();
        if (isset($ChildFiles) && StorageHelper::disk()->exists($ChildFiles->File_Path)) 
        {   

            $key = hash('sha256', env('ENCRYPTION_KEY'), true);
            $encryptedData = StorageHelper::disk()->get($ChildFiles->File_Path);
            if (empty($encryptedData) || strlen($encryptedData) < 16) 
            {
                throw new \Exception('Invalid or corrupted encrypted data');
            }
                $iv = substr($encryptedData, 0, 16);
                $cipherText = substr($encryptedData, 16);
                $decryptedData = openssl_decrypt(
                    $cipherText,
                    'aes-256-cbc',
                    $key,
                    OPENSSL_RAW_DATA,  // Critical for handling binary data properly
                    $iv
                );
                
                if ($decryptedData === false) {
                    $error = openssl_error_string();
                    throw new \Exception("Decryption failed: {$error}");
                }
                
                $decryptedFileName = str_replace('.enc', '', basename($ChildFiles->File_Path));
                $tempFilePath = "temp/decrypted_" . time() . "_{$decryptedFileName}";
                $extension = strtolower(pathinfo($decryptedFileName, PATHINFO_EXTENSION));
                $mimeTypes = [
                    'pdf' => 'application/pdf',
                    'doc' => 'application/msword',
                    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'xls' => 'application/vnd.ms-excel',
                    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'ppt' => 'application/vnd.ms-powerpoint',
                    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'txt' => 'text/plain',
                    'csv' => 'text/csv',
                    
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'bmp' => 'image/bmp',
                    'svg' => 'image/svg+xml',
                    'webp' => 'image/webp',
                    
                    'mp3' => 'audio/mpeg',
                    'wav' => 'audio/wav',
                    'ogg' => 'audio/ogg',
                    'flac' => 'audio/flac',
                    'aac' => 'audio/aac',
                    
                    'mp4' => 'video/mp4',
                    'mov' => 'video/quicktime',
                    'avi' => 'video/x-msvideo',
                    'mkv' => 'video/x-matroska',
                    'webm' => 'video/webm',
                    'wmv' => 'video/x-ms-wmv',
                    'flv' => 'video/x-flv',
                    
                    'zip' => 'application/zip',
                    'rar' => 'application/x-rar-compressed',
                    'tar' => 'application/x-tar',
                    'gz' => 'application/gzip',
                    '7z' => 'application/x-7z-compressed',
                    'html' => 'text/html',
                    'css' => 'text/css',
                    'js' => 'application/javascript',
                    'json' => 'application/json',
                    'xml' => 'application/xml'
                ];
                
                // Set MIME type based on extension or detect if not in our map
                if (isset($mimeTypes[$extension])) {
                    $mimeType = $mimeTypes[$extension];
        
                } else {
                    // Fallback to file detection - may not be accurate for all file types
                    // but better than nothing for unknown extensions
                    if (function_exists('mime_content_type')) {
                        // Create a temporary file to use mime_content_type
                        $tempFile = tempnam(sys_get_temp_dir(), 'file');
                        file_put_contents($tempFile, $decryptedData);
                        $mimeType = mime_content_type($tempFile);
                        unlink($tempFile); // Clean up
                    } else if (class_exists('finfo')) {
                        $finfo = new \finfo(FILEINFO_MIME_TYPE);
                        $mimeType = $finfo->buffer($decryptedData);
                    } else {
                        // If all detection methods fail, use binary as default
                        $mimeType = 'application/octet-stream';
                    }
                }
                
                // Store the decrypted file with proper content type
                StorageHelper::disk()->put($tempFilePath, $decryptedData, [
                    'ContentType' => $mimeType
                ]);
                
                // Generate a temporary URL with sufficient time window
                $fileExtension = pathinfo($ChildFiles->File_Path, PATHINFO_EXTENSION);
                // Get MIME type dynamically

                $mimeType = match (strtolower($extension)) {
                    'mp4'  => 'video/mp4',
                    'mov'  => 'video/quicktime',
                    'avi'  => 'video/x-msvideo',
                    'pdf'  => 'application/pdf',
                    'txt'  => 'text/plain',
                    'jpg'  => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png'  => 'image/png',
                    'gif'  => 'image/gif',
                    'doc', 'docx' => 'application/msword',
                    'xls', 'xlsx' => 'application/vnd.ms-excel',
                    'zip'  => 'application/zip',
                    default => 'application/octet-stream' // Fallback for unknown types
                };
                $newUrl = StorageHelper::temporaryUrl($tempFilePath, 30);
            } else {
                $mimeType='';
            $newUrl = "No";
        }
      
        return ['success' => true,  'NewURLshow' => $newUrl,'mimeType' => $mimeType];     

        if ($AwsResponse['status'] == true) 
        {
            return response()->json(['status' => true, 'AwsResponse' => $AwsResponse, 'message' => 'File found successfully'], 200);
        } 
        else
        {
            return response()->json(['status' => false, 'AwsResponse' => '', 'message' => 'File not found or cannot be accessed'], 200);
        }
    }
    
    /**
     * Safely parse a date that may come from OCR/AI extraction, where missing
     * values arrive as literal strings like "Unavailable" or "N/A". Returns a
     * Carbon instance, or null when the value is empty / a placeholder /
     * unparseable (so callers never hit Carbon's InvalidFormatException).
     */
    private function safeParse($value)
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        $placeholders = ['', 'unavailable', 'n/a', 'na', 'not available', 'none', '-', '--'];
        if (in_array(strtolower($value), $placeholders, true)) {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Format a possibly-placeholder date, falling back to a readable label
     * instead of throwing.
     */
    private function safeDate($value, $format = 'd M Y', $fallback = 'Unavailable')
    {
        $c = $this->safeParse($value);
        return $c ? $c->format($format) : $fallback;
    }

    function getFormattedExpiryStatus($endDate)
    {
        $end = $this->safeParse($endDate);
        if (!$end) {
            return 'Unavailable';
        }
        $start = Carbon::today();
        $daysDiff = $start->diffInDays($end, false);
        if ($daysDiff < 0) 
        {
            return $end->format('d M Y')."  (Expired " . abs($daysDiff) . " days ago)";
        }
        else
        {
            return $end->format('d M Y')."  (Expires in " . ($daysDiff + 1) . " days)";
        }
    }

   private function  TotalExpensessSinceJoing($id)
   {
        $rid = $this->resort->resort_id;

        // These are Maldives government fees, always denominated in MVR. Normalise
        // each stored amount to MVR (convert any USD-stored row up to MVR) so the
        // cards can render a plain "MVR x" regardless of the resort's display
        // currency. Previously the figures were converted to USD and shown with
        // the resort's '$' symbol, which mislabeled MVR govt fees.
        $toMvr = function ($amount, $currency = 'MVR') use ($rid) {
            $cur = strtoupper(trim((string) $currency));
            if ($cur === 'MVR') {
                return (float) $amount;
            }
            // USD / $ stored row -> MVR
            return (float) Common::RateConversion('DollerToMVR', (float) $amount, $rid);
        };
        $sumPaidMvr = function ($rows, string $amountCol) use ($toMvr) {
            return (float) $rows->sum(fn ($r) => $toMvr($r->{$amountCol}, $r->Currency ?? 'MVR'));
        };

        // Actual expenses = the PAID records, summed live in MVR.
        // The old total_expensess_since_joings snapshot was written once at sync
        // time from the cost config, so it never reflected later edits and showed
        // 0 wherever the config lookup missed — no longer used for the fee totals.
        $data = [];
        $data['totalWorkPermitAmount']            = $sumPaidMvr(\App\Models\WorkPermit::where('resort_id',$rid)->where('employee_id',$id)->where('Status','Paid')->get(), 'Amt');
        $data['totalQuotaSlotPayment']            = $sumPaidMvr(\App\Models\QuotaSlotRenewal::where('resort_id',$rid)->where('employee_id',$id)->where('Status','Paid')->get(), 'Amt');
        $data['totalInsurancePayment']            = $sumPaidMvr(\App\Models\EmployeeInsurance::where('resort_id',$rid)->where('employee_id',$id)->where('Status','Paid')->get(), 'Premium');
        $data['totalWorkPermitMedicalFeePayment'] = $sumPaidMvr(\App\Models\WorkPermitMedicalRenewal::where('resort_id',$rid)->where('employee_id',$id)->where('Status','Paid')->get(), 'Amt');
        $data['totalVisa']                        = $sumPaidMvr(\App\Models\VisaRenewal::where('resort_id',$rid)->where('employee_id',$id)->where('Status','Paid')->get(), 'Amt');

        // Deposit is a one-time amount with no per-payment record — taken from the
        // snapshot row written at sync time.
        $snapshot = TotalExpensessSinceJoing::where('resort_id',$rid)->where('employees_id',$id)->get();
        $depositAmount = (float) ($snapshot->sum('Deposit_Amt') ?? 0.00);

        // Live fallback: manually-managed employees that were never run through the
        // Xpact sync have no snapshot row, so the deposit came out MVR 0 even though
        // a nationality deposit rate exists. Look the rate up directly from
        // visa_nationalities using the SAME flexible matcher the sync uses
        // (case-insensitive, tolerant of demonym<->country e.g. Indian<->India).
        if ($depositAmount <= 0) {
            $emp    = Employee::where('id', $id)->where('resort_id', $rid)->first(['nationality']);
            $natRaw = $emp ? strtolower(trim((string) $emp->nationality)) : '';
            if ($natRaw !== '') {
                $rate = \App\Models\VisaNationality::where('resort_id', $rid)
                    ->where(function ($q) use ($natRaw) {
                        $q->whereRaw('LOWER(TRIM(nationality)) = ?', [$natRaw])
                          ->orWhereRaw("? LIKE CONCAT(LOWER(TRIM(nationality)), '%')", [$natRaw])
                          ->orWhereRaw("LOWER(TRIM(nationality)) LIKE CONCAT(?, '%')", [$natRaw]);
                    })
                    ->first(['amt']);
                if ($rate) {
                    $depositAmount = (float) $rate->amt;
                }
            }
        }
        $data['totalDepositAmount'] = $depositAmount;

        return $data;
   }
}
 