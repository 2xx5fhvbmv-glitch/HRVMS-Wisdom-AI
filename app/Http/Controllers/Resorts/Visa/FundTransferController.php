<?php

namespace App\Http\Controllers\Resorts\Visa;
use DB;
use Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Helpers\Common;
use App\Models\Resorts;
use App\Models\VisaTransectionHistory;
use App\Models\VisaWallets;
use App\Models\Employee;
use App\Models\WorkPermit;
use App\Models\QuotaSlotRenewal;
use App\Models\EmployeeInsurance;
use App\Models\WorkPermitMedicalRenewal;
use App\Models\VisaRenewal;
class FundTransferController extends Controller
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


    public function VisaWalletToWalletTransfer(Request $request)
    {
        $from_wallet = base64_decode($request->from_wallet);
        $to_wallet = base64_decode($request->to_wallet);
        $Amt      =  $request->Amt;
        $comments = $request->comments;
    
        
        $collection='';
        $path_path = config('settings.FundTransfer') . '/' . Auth::guard('resort-admin')->user()->resort->resort_id;
        if ($request->hasFile('transectionFile')) 
        {
            $imageFile = $request->file('transectionFile');
            $imageName =  $imageFile->getClientOriginalName();
            $imageFile->move($path_path, $imageName);
            $collection = $imageName;
        }

        $from_wallet_Amt = VisaWallets::where('resort_id', $this->resort->resort_id) ->where('id', $from_wallet)->first();
        if ($from_wallet_Amt->Amt < $Amt) 
        {
            return response()->json([
                'success' => false,
                'msg' => 'Validation failed',
                'errors' => [
                    'from_wallet' => ['Insufficient balance in the ' . $from_wallet_Amt->WalletName]
                ]
            ], 422);
        }
        DB::beginTransaction();
        try { 
                $to_wallet_Amt = VisaWallets::where('resort_id', $this->resort->resort_id) ->where('id', $to_wallet)->first();
                $to_wallet_Amt->Amt += $Amt;
                $to_wallet_Amt->save();
                $from_wallet_Amt->Amt = $from_wallet_Amt->Amt - $Amt;
                $from_wallet_Amt->save();
                $VisaTransectionHistory = VisaTransectionHistory::create([
                                                                            'resort_id' => $this->resort->resort_id,
                                                                            'Amt' => $Amt,
                                                                            'to_wallet_realAmt' => $to_wallet_Amt->Amt,
                                                                            'from_wallet_realAmt' => $from_wallet_Amt->Amt, 
                                                                            'Payment_Date' => Carbon::now(),
                                                                            'file' => $collection,
                                                                            'comments' => $comments
                                                                        ]);
            VisaTransectionHistory::where("id", $VisaTransectionHistory->id)->update(['to_wallet' => $to_wallet, 'from_wallet' => $from_wallet,]);
            DB::commit();
            return response()->json([
                                        'success' => true,
                                        'msg' => 'Fund Transferred  successfully',
                                    ], 200);
        } 
        catch (\Exception $e) 
        {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => 'Failed to add Fund Transferred',
            ], 500);
        }

    }

    public function TransectionHistory(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $unified  = collect();

        // ── 1. Wallet → Wallet transfers (and wallet → employee refunds) ──
        VisaTransectionHistory::with(['toWallet', 'fromWallet'])
            ->where('resort_id', $resort_id)
            ->orderBy('id', 'desc')
            ->get()
            ->each(function ($t) use (&$unified) {
                $fromLabel = !empty($t->fromWallet) ? $t->fromWallet->WalletName : 'Wallet Not Found';

                if (!$t->to_wallet) {
                    // Deposit refund — money left the wallet and went to an employee.
                    $emp = Employee::with(['resortAdmin'])->where('id', $t->Employee_id)->first();
                    $toLabel = ($emp && $emp->resortAdmin)
                        ? trim($emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name) . ' (Deposit Refund)'
                        : 'Employee Not Found';
                } else {
                    $toLabel = !empty($t->toWallet) ? $t->toWallet->WalletName : 'Wallet Not Found';
                }

                $unified->push((object) [
                    'Date'       => optional($t->Payment_Date)->format('d M Y') ?: '',
                    'FromWallet' => $fromLabel,
                    'ToWallet'   => $toLabel,
                    'Amount'     => (float) $t->Amt,
                    'sort_ts'    => optional($t->Payment_Date)->timestamp ?? 0,
                ]);
            });

        // Helper: render "Fee Type — Employee Name" for fee-payment rows.
        $feeRecipientLabel = function ($emp, string $feeLabel) {
            if ($emp && $emp->resortAdmin) {
                $name = trim($emp->resortAdmin->first_name . ' ' . $emp->resortAdmin->last_name);
                return $feeLabel . ' — ' . $name;
            }
            return $feeLabel;
        };

        // ── 2. Work Permit fees marked Paid ──
        WorkPermit::with(['employee.resortAdmin'])
            ->where('resort_id', $resort_id)
            ->where('Status', 'Paid')
            ->get()
            ->each(function ($r) use (&$unified, $feeRecipientLabel) {
                $date = $r->Payment_Date ?: $r->updated_at;
                $unified->push((object) [
                    'Date'       => $date ? Carbon::parse($date)->format('d M Y') : '',
                    'FromWallet' => '—',
                    'ToWallet'   => $feeRecipientLabel($r->employee, 'Work Permit Fee'),
                    'Amount'     => (float) $r->Amt,
                    'sort_ts'    => $date ? Carbon::parse($date)->timestamp : 0,
                ]);
            });

        // ── 3. Quota Slot fees marked Paid ──
        QuotaSlotRenewal::with(['employee.resortAdmin'])
            ->where('resort_id', $resort_id)
            ->where('Status', 'Paid')
            ->get()
            ->each(function ($r) use (&$unified, $feeRecipientLabel) {
                $date = $r->Payment_Date ?: $r->updated_at;
                $unified->push((object) [
                    'Date'       => $date ? Carbon::parse($date)->format('d M Y') : '',
                    'FromWallet' => '—',
                    'ToWallet'   => $feeRecipientLabel($r->employee, 'Slot Fee'),
                    'Amount'     => (float) $r->Amt,
                    'sort_ts'    => $date ? Carbon::parse($date)->timestamp : 0,
                ]);
            });

        // ── 4. Insurance — Status was backfilled to 'Paid' by the 2026_05_14 migration ──
        EmployeeInsurance::with(['employee.resortAdmin'])
            ->where('resort_id', $resort_id)
            ->where('Status', 'Paid')
            ->get()
            ->each(function ($r) use (&$unified, $feeRecipientLabel) {
                $date = $r->paid_date ?: $r->insurance_start_date;
                $unified->push((object) [
                    'Date'       => $date ? Carbon::parse($date)->format('d M Y') : '',
                    'FromWallet' => '—',
                    'ToWallet'   => $feeRecipientLabel($r->employee, 'Insurance Premium'),
                    'Amount'     => (float) $r->Premium,
                    'sort_ts'    => $date ? Carbon::parse($date)->timestamp : 0,
                ]);
            });

        // ── 5. Work Permit Medical — Status='Paid' (migration-backfilled) ──
        WorkPermitMedicalRenewal::with(['employee.resortAdmin'])
            ->where('resort_id', $resort_id)
            ->where('Status', 'Paid')
            ->get()
            ->each(function ($r) use (&$unified, $feeRecipientLabel) {
                $date = $r->paid_date ?: $r->start_date;
                $unified->push((object) [
                    'Date'       => $date ? Carbon::parse($date)->format('d M Y') : '',
                    'FromWallet' => '—',
                    'ToWallet'   => $feeRecipientLabel($r->employee, 'Work Permit Medical'),
                    'Amount'     => (float) $r->Amt,
                    'sort_ts'    => $date ? Carbon::parse($date)->timestamp : 0,
                ]);
            });

        // ── 6. Visa Renewal — Status='Paid' (migration-backfilled) ──
        VisaRenewal::with(['employee.resortAdmin'])
            ->where('resort_id', $resort_id)
            ->where('Status', 'Paid')
            ->get()
            ->each(function ($r) use (&$unified, $feeRecipientLabel) {
                $date = $r->paid_date ?: $r->start_date;
                $unified->push((object) [
                    'Date'       => $date ? Carbon::parse($date)->format('d M Y') : '',
                    'FromWallet' => '—',
                    'ToWallet'   => $feeRecipientLabel($r->employee, 'Visa Renewal'),
                    'Amount'     => (float) $r->Amt,
                    'sort_ts'    => $date ? Carbon::parse($date)->timestamp : 0,
                ]);
            });

        // Newest first.
        $unified = $unified->sortByDesc('sort_ts')->values();

        if ($request->ajax()) {
            return datatables()->of($unified)
                ->editColumn('Date', fn ($row) => $row->Date)
                ->editColumn('FromWallet', fn ($row) => $row->FromWallet)
                ->editColumn('ToWallet', fn ($row) => $row->ToWallet)
                ->editColumn('Amount', fn ($row) => number_format(Common::convertToDisplayCurrency($row->Amount, 'MVR'), 2))
                ->rawColumns(['Date', 'FromWallet', 'ToWallet', 'Amount'])
                ->make(true);
        }
    }

    /**
     * Full-page listing of wallet-to-wallet transfer + deposit refund
     * transactions. Linked from the "View All" link on the dashboard's
     * "Transfer Between Wallet" card. Reuses the existing TransectionHistory
     * AJAX endpoint to populate the DataTable.
     */
    public function TransectionHistoryIndex()
    {
        $page_title = 'Transaction History';
        return view('resorts.Visa.dashboard.transactionhistory', compact('page_title'));
    }

}
 

  
