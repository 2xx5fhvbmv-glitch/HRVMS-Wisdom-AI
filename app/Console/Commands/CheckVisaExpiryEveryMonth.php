<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use Carbon\Carbon;
use App\Models\ParentAttendace;
use Auth;
use App\Events\ResortNotificationEvent;
use App\Models\Compliance;
use App\Models\BreakAttendaces;
use App\Helpers\Common;
class CheckVisaExpiryEveryMonth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

     protected $signature = 'Monthly:CheckEveryVisaModule';
     protected $description = 'Visa Expiry Check Every Month';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
     
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        
          // Visa Module   
               $flag = 'all';
               $filterStart = Carbon::now()->startOfMonth();
               $filterEnd = Carbon::now()->endOfMonth();
               $overworkedEmployeesVisa = [];
               $Employee = Employee::with(['resortAdmin','position', 'department','VisaRenewal.VisaChild','WorkPermitMedicalRenewal.WorkPermitMedicalRenewalChild','WorkPermit','EmployeeInsurance.InsuranceChild','QuotaSlotRenewal'])->where("nationality", '!=', "Maldivian")->get()
               ->map(function ($employee) use (&$overworkedEmployeesVisa) 
               {

                    $employee->Emp_name        = $employee->resortAdmin->first_name . ' ' . $employee->resortAdmin->last_name;
                    $employee->Emp_id          = $employee->Emp_id;
                    $employee->Department_name = $employee->department->name;
                    $employee->Position_name   = $employee->position->position_title;
                    $employee->ProfilePic      = Common::getResortUserPicture($employee->resortAdmin->id);

                    $newVisaMArray = [];
                    $ExpiryDocument = [];
                    // Visa
                    $visa = $employee->VisaRenewal;
                    if ($visa && $this->isExpired($visa->end_date)) {
                         $employee->VisaExpiryDate = $this->getFormattedExpiryStatus($visa->end_date);
                         $employee->VisaExpiryExpiryAmt = $visa->Amt;
                         $newVisaMArray[] = [
                              'type' => 'Visa',
                              'ExpiryDate' => $this->getFormattedExpiryStatus($visa->end_date),
                              'amount' => $visa->Amt
                         ];
                    }

                    // Insurance
                    $insurance = $employee->EmployeeInsurance()
                         ->where('employee_id', $employee->id)
                         ->where('resort_id', $employee->resort_id)
                         ->orderBy('id', 'desc')
                         ->first();

                    if ($insurance && $this->isExpired($insurance->insurance_end_date)) {
                         $employee->InsuranceExpiryDate = $this->getFormattedExpiryStatus($insurance->insurance_end_date);
                         $employee->Premium = $insurance->Premium;
                         $newVisaMArray[] = [
                              'type' => 'Medical International Insurance',
                              'ExpiryDate' => $this->getFormattedExpiryStatus($insurance->insurance_end_date),
                              'amount' => $insurance->Premium
                         ];
                    }

                    // Work Permit
                    $currentWP = $employee->WorkPermit->where('Status', 'Unpaid')->filter(fn($item) => $this->isExpired($item->Due_Date))->sortByDesc('id')->first();
                    if($currentWP) 
                    {
                         $employee->WorkPermitExpiryDate = $this->getFormattedExpiryStatus($currentWP->Due_Date);
                         $employee->WorkPermitAmt = number_format($currentWP->Amt, 2);
                         $newVisaMArray[] = [ 'type' => 'Work Permit','ExpiryDate' => $this->getFormattedExpiryStatus($currentWP->Due_Date),'amount' => number_format($currentWP->Amt, 2)];
                    }
                    // Medical Test
                    $med = $employee->WorkPermitMedicalRenewal;
                    if ($med && $this->isExpired($med->end_date)) 
                    {
                         $employee->WorkPermitMedicalPermitExpiryDate = $this->getFormattedExpiryStatus($med->end_date);
                         $employee->WorkPermitMedicalPermitAmt = number_format($med->Amt, 2);
                         $newVisaMArray[] =  [
                                                  'type' => 'Work Permit Medical Test Fee',
                                                  'ExpiryDate' => $this->getFormattedExpiryStatus($med->end_date),
                                                  'amount' => number_format($med->Amt,2)
                                             ];
                    }
                    // Quota Slot
                    $currentQuota = $employee->QuotaSlotRenewal->filter(fn($item) => $this->isExpired($item->Due_Date))->where('Status', 'Unpaid')->first();
                         if ($currentQuota) 
                         {
                              $employee->QuotaSlotAmtForThisMonth = $this->getFormattedExpiryStatus($currentQuota->Due_Date);
                              $employee->QuotaSlotAmtForThisMonthAmt = $currentQuota->Amt;
                              $newVisaMArray[] = [
                                   'type' => 'Quota Slot',
                                   'ExpiryDate' => $this->getFormattedExpiryStatus($currentQuota->Due_Date),
                                   'amount' => number_format($currentQuota->Amt, 2)
                              ];
                         }
                         if (!empty($newVisaMArray)) 
                         {
                              $ExpiryDocument[$employee->Emp_name] = $newVisaMArray;
                              if (!empty($ExpiryDocument))
                              {    
                                   $overworkedEmployeesVisa[] = [
                                        'resort_id' => $employee->resort_id,
                                        'employee_id' => $employee->id,
                                        'module_name' => 'Visa',
                                        'compliance_breached_name' => 'Expired Visa Documents',
                                        'description' => $this->stringifyExpiryDocument($ExpiryDocument),
                                        'reported_on' => Carbon::now(),
                                        'status' => 'Breached'
                                   ];
                              }
                         }
                    return !empty($newVisaMArray) ? $employee : null;
               })->filter();
                if(!empty($overworkedEmployeesVisa))
                {
                    Compliance::insert($overworkedEmployeesVisa);
                    $this->info('Visa Expiry Compliance Created successfully');
                }
                else
                {
                    $this->info('No Visa Expiry Compliance Created');
                }
          // VisaEnd
    }

    public function isExpired($date)
    {
        return  Carbon::parse($date)->lt(Carbon::today());
    }
    function stringifyExpiryDocument(array $expiryDocument, bool $asArray = true)
    {
        $out = [];
        foreach ($expiryDocument as $employeeName => $docs) 
        {

            // Build the part after "EmployeeName: "
            $docParts = array_map(function ($doc) {
                return sprintf(
                        '%s — %s, MVR %s',
                        $doc['type'],
                        $doc['ExpiryDate'],
                        $doc['amount']
                );
            }, $docs);

            $out = $employeeName . ': ' . implode('; ', $docParts);
        }

        // Return as‑is (separate strings) or one single string
        return $asArray ? $out : implode("\n", $out);
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
}
