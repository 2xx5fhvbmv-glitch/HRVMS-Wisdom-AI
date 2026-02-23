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
use App\Models\ResortDepartment;
use App\Models\Admin;
use Carbon\Carbon;
use URL;
class JobAdvertisementController extends Controller
{
    public $resort;
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }

    public function index()
    {
        $page_title = "Job Ad Templates";
        return view('resorts.talentacquisition.jobadvertisement.index', compact('page_title'));
    }

    public function getList(Request $request)
    {
        $resort_id = $this->resort->resort_id;
        $storagePath = config('settings.Resort_JobAdvertisement');

        $jobAds = JobAdvertisement::select([
                'job_advertisements.id',
                'job_advertisements.Jobadvimg',
                'job_advertisements.Resort_id',
                'job_advertisements.created_by',
                'job_advertisements.created_at',
                'job_advertisements.updated_at',
            ])
            ->where('job_advertisements.Resort_id', $resort_id)
            ->orderBy('job_advertisements.id', 'DESC');

        return datatables()->of($jobAds)
            ->addColumn('Preview', function ($row) use ($storagePath) {
                $imgUrl = URL::asset($storagePath.'/'.$row->Resort_id.'/'.$row->Jobadvimg);
                return '<a href="'.$imgUrl.'" target="_blank"><img src="'.$imgUrl.'" alt="Template" style="max-height:60px; max-width:100px;" class="img-fluid rounded"></a>';
            })
            ->addColumn('FileName', function ($row) {
                return htmlspecialchars($row->Jobadvimg, ENT_QUOTES, 'UTF-8');
            })
            ->addColumn('UploadedBy', function ($row) {
                $admin = Admin::select('first_name', 'last_name')->where('id', $row->getRawOriginal('created_by'))->first();
                if($admin) {
                    return ucwords($admin->first_name.' '.$admin->last_name);
                }
                return '-';
            })
            ->addColumn('UploadedAt', function ($row) {
                return $row->updated_at ?? $row->created_at ?? '-';
            })
            ->addColumn('action', function ($row) {
                $deleteUrl = asset('resorts_assets/images/trash-red.svg');
                $imgUrl = URL::asset(config('settings.Resort_JobAdvertisement').'/'.$row->Resort_id.'/'.$row->Jobadvimg);
                return '
                    <a href="'.$imgUrl.'" target="_blank" class="btn-tableIcon btnIcon-skyblue"><i class="fa-regular fa-eye"></i></a>
                    <a href="javascript:void(0)" class="btn-lg-icon icon-bg-red delete-row-btn"
                       data-id="'. htmlspecialchars($row->id, ENT_QUOTES, 'UTF-8') . '">
                        <img src="' . $deleteUrl . '" alt="Delete" class="img-fluid" />
                    </a>
                ';
            })
            ->rawColumns(['Preview', 'action'])
            ->make(true);
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $jobAd = JobAdvertisement::where('id', $id)
                ->where('Resort_id', $this->resort->resort_id)
                ->first();

            if(!$jobAd) {
                return response()->json(['success' => false, 'message' => 'Template not found.'], 404);
            }

            $path = config('settings.Resort_JobAdvertisement').'/'.$jobAd->Resort_id.'/'.$jobAd->Jobadvimg;
            if(File::exists(public_path($path))) {
                File::delete(public_path($path));
            }

            $jobAd->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Template removed successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: ".$e->getFile());
            \Log::emergency("Line: ".$e->getLine());
            \Log::emergency("Message: ".$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to remove template.'], 500);
        }
    }

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

                // Return rendered views to refresh dashboard sections
                $resort_id = $this->resort->resort_id;
                $rank = (int) ($this->resort->GetEmployee->rank ?? 0);
                $effectiveRank = $rank;
                if (!in_array($rank, [3, 7, 8])) {
                    $userDeptName = ResortDepartment::where('id', $this->resort->GetEmployee->Dept_id)->value('name');
                    $userPositionTitle = $this->resort->GetEmployee->position->position_title ?? '';
                    if (stripos($userDeptName, 'Accounting') !== false || stripos($userDeptName, 'Finance') !== false
                        || stripos($userPositionTitle, 'Finance') !== false) {
                        $effectiveRank = 7;
                    } elseif (stripos($userDeptName, 'Human Resources') !== false || stripos($userPositionTitle, 'Human Resources') !== false) {
                        $effectiveRank = 3;
                    }
                }
                $getNotifications['FreshVacancies'] = Common::GetTheFreshVacancies($resort_id, 'Active', $effectiveRank);
                $view = view('resorts.renderfiles.FreshVacancies', compact('getNotifications'))->render();
                $TodoData = Common::GmApprovedVacancy($resort_id, $effectiveRank);
                $Todolistview = view('resorts.renderfiles.TaTodoList', compact('TodoData'))->render();

                return response()->json(['success' => true, 'ta_child_id'=> $request->ta_child_id, 'view' => $view, 'Todolistview' => $Todolistview, 'message' => 'Job Advertisement Link Acitivate now.']);
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
