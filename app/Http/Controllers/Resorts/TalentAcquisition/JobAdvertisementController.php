<?php

namespace App\Http\Controllers\Resorts\TalentAcquisition;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobAdvertisement;
use Validator;
use Auth;
use App\Helpers\Common;
use File;
use DB;
use App\Models\ApplicationLink;
use Carbon\Carbon;
class JobAdvertisementController extends Controller
{


    public function StoreJobAvd(Request $request)
    {
        $validator =  Validator::make($request->all(), [
            'Jobadvimg' => 'required|file|mimes:jpg,jpeg,png,gif|max:2048',
        ], [
            'Jobadvimg.max' => 'The file size must not exceed 2MB.',
            'Jobadvimg.mimes' => 'The file must be an image (jpg, jpeg, png, gif)',
            'Jobadvimg.required' => 'Please select an image',
        ]);
        if($validator->fails())
        {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        $resort_id= Auth::guard('resort-admin')->user()->resort_id;
        try
        {
            DB::beginTransaction();
                if ($request->hasFile('Jobadvimg'))
                {

                    $path_profile_image = config('settings.Resort_JobAdvertisement').'/'. Auth::guard('resort-admin')->user()->resort->resort_id;



                    $fileName  = $request->file('Jobadvimg')->getClientOriginalName();


                        if(File::exists(public_path($path_profile_image.'/'.  $fileName)))
                        {
                            File::delete(public_path($path_profile_image.'/'.  $fileName));
                        }

                        Common::uploadFile($request->file('Jobadvimg'), $fileName, $path_profile_image);

                        JobAdvertisement::updateOrCreate([
                                "Resort_id" => $resort_id,
                        ],[
                            "Jobadvimg" => $fileName,
                        ]);
                        DB::commit();
                        return response()->json(['success' => true, 'message' => 'Job Advertisement Uploaded successfully.']);
                    }

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

    public function GenrateAdvLink(Request $request)
    {

        DB::beginTransaction();
        try
        {


            if(isset($request->link_Expiry_date))
            {
                if(isset($request->ExtendFlag ) && $request->ExtendFlag =="extendata") // Exitend Page only
                {
                    $new_link_Expiry_date = Carbon::createFromFormat('d/m/Y', $request->link_Expiry_date)->format('Y-m-d');

                    $a = ApplicationLink::find($request->ApplicationId);

                    if(isset($a) &&  $a->link_Expiry_date == $new_link_Expiry_date)
                    {
                        return response()->json(['success' => false,'message' => 'Same Expiry Date You Selected.']);
                    }
                    else
                    {

                        if(isset($a->Old_ExpiryDate) && $a->Old_ExpiryDate !="0000-00-00" )
                        {
                            $json_data =  json_decode($a->Old_ExpiryDate);

                            array_push($json_data,$a->link_Expiry_date);

                        }
                        else
                        {
                            $array=array($a->link_Expiry_date);
                            {
                                $json_data = json_encode($array);
                            }
                        }

                        
                        $a->Old_ExpiryDate = $json_data;
                        $a->link_Expiry_date = $new_link_Expiry_date;
                        $a->save();
                        return response()->json([
                            'success' => true,
                            'message' => "Expiry Date Extended to {$request->link_Expiry_date}"
                        ]);
                    }
                }
                else // New Application Link Genrate
                {
                    ApplicationLink::updateOrCreate([
                        "Resort_id"=> $request->Resort_id,
                    "ta_child_id" =>  $request->ta_child_id,
                ],[
                    "link_Expiry_date"=> Carbon::createFromFormat('d/m/Y', $request->link_Expiry_date)->format('Y-m-d'),
                    "link"=> $request->link,
                    "Resort_id"=> $request->Resort_id,
                    "ta_child_id" =>  $request->ta_child_id,
                ]);
                DB::commit();
                return response()->json(['success' => true, 'ta_child_id'=> $request->ta_child_id,'message' => 'Job Advertisement Link Acitivate now.']);
                }
            }
            else
            {
                return response()->json(['success' => false,'message' => 'Requirtment is started so you cant extend the  Expiry Date.']);
            }
            


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
}
