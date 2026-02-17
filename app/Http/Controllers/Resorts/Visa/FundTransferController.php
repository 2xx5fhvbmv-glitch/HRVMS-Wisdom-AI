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
class FundTransferController extends Controller
{
    
        protected $resort;
        protected $underEmp_id=[];
        public function __construct()
        {
            $this->resort = $resortId = auth()->guard('resort-admin')->user();
            if(!$this->resort) return;
            if($this->resort->is_master_admin == 0){
                $reporting_to = $this->globalUser->GetEmployee->id;
                $this->underEmp_id = Common::getSubordinates($reporting_to);
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
        $transectionHistory = VisaTransectionHistory::with(['toWallet','fromWallet'])->where('resort_id', $resort_id)->orderBy('id', 'desc')->get()
            ->map(function ($transection) 
            {

                if(!$transection->to_wallet)
                {
                    $employee = Employee::with(['resortAdmin'])->where('id', $transection->Employee_id)->first();
                    $transection->ToWalletDifferentName = $employee->resortAdmin->first_name . ' - ' . $employee->resortAdmin->last_name; 
                }
                else
                {
                    $transection->ToWalletDifferentName = !empty($transection->fromWallet ) ? $transection->fromWallet->WalletName :' Wallet Not Found';
                }


                $transection->fromWalletName = !empty($transection->fromWallet ) ? $transection->fromWallet->WalletName :' Wallet Not Found';
                return $transection;
            });
            
            if ($request->ajax()) 
            {
             return datatables()->of($transectionHistory)
                ->editColumn('Date', function ($row) 
                    {
                        return $row->Payment_Date->format('d M Y');
                    })
                    ->editColumn('FromWallet', function ($row) 
                    {
                        return $row->fromWalletName;
                    })
                    ->editColumn('ToWallet', function ($row) 
                    {
                        return $row->ToWalletDifferentName;
                    })      
                    ->editColumn('Amount', function ($row) 
                    {
                        return number_format($row->Amt, 2);
                    })      
                    ->rawColumns(['Date','FromWallet','ToWallet','Amount'])
                    ->make(true);
        }      
      
    }
  
}
 

  
