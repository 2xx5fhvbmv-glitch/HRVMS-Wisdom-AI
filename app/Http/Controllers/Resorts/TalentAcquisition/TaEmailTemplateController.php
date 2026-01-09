<?php

namespace App\Http\Controllers\Resorts\TalentAcquisition;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TaEmailTemplate;
use App\Helpers\Common;
use Auth;
use DB;
class TaEmailTemplateController extends Controller
{
    public function index()
    {
        $resort_id= Auth::guard('resort-admin')->user()->resort_id;
        $emailTamplate  = TaEmailTemplate::where('Resort_id',$resort_id)->get();
        $resortImg = Common::GetResortLogo($resort_id);
        $page_title="Email Template";
        return view('resorts.talentacquisition.Emaildrafting.index',compact('resortImg','emailTamplate','page_title'));
    }

    public function Show($id)
    {
        DB::beginTransaction();
        try
        {
            $resort_id= Auth::guard('resort-admin')->user()->resort_id;
            $emailTamplate  = TaEmailTemplate::where('Resort_id',$resort_id)->where("id",$id)->first();
            return response()->json(['success' => true, 'emailTamplate' => $emailTamplate], 200);
        }
       catch (\Exception $e)
       {
           DB::rollBack();
           \Log::emergency("File: " . $e->getFile());
           \Log::emergency("Line: " . $e->getLine());
           \Log::emergency("Message: " . $e->getMessage());
           return response()->json(['error' => 'Failed to Upload  data'], 500);
       }
    }

    public function Store(Request $request)
    {
        DB::beginTransaction();
        try {
            $resort_id = Auth::guard('resort-admin')->user()->resort_id;

            // Ensure placeholder extraction works
            $placeholders = TaEmailTemplate::extractPlaceholders($request->MailTemplete) ?? [];

            $emailTemplate = TaEmailTemplate::updateOrCreate(
                ["id" => $request->id],
                [
                    "Resort_id" => $resort_id,
                    "TempleteName" => $request->TempleteName,
                    "MailTemplete" => $request->MailTemplete,
                    "MailSubject" => $request->MailSubject,
                    "Placeholders" => $placeholders, // Save the extracted placeholders
                ]
            );

            DB::commit();
            \Log::info($request->body);
            \Log::info($placeholders);
            return response()->json(['success' => true, 'message' => "Email Template Updated"], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());
            \Log::info($request->body);
            \Log::info($placeholders);

            return response()->json(['error' => 'Failed to Upload data'], 500);
        }
    }


}
