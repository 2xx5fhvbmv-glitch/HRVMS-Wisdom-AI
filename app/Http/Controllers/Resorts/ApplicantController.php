<?php
namespace App\Http\Controllers\Resorts;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Session;
use DB;
use BrowserDetect;
use Route;
use File;
use App\Models\Resort;
use App\Models\ResortAdmin;
use App\Models\ResortDivision;
use App\Models\Division;
use App\Models\State;
use App\Models\Country;
use App\Models\City;
use App\Models\Vacancies;
use App\Models\Questionnaire;
use App\Models\QuestionnaireChild;
use App\Models\VideoQuestion;
use App\Models\ResortLanguages;
use App\Models\Temp_language_video_store;
use App\Models\Applicant_form_data;
use App\Models\Work_experience_applicant_form;
use App\Models\Education_applicant_form;
use App\Models\Applicant_form_job_assessment;
use App\Models\ApplicantLanguage;
use App\Models\TermsAndCondition;
use App\Models\ApplicantWiseStatus;
use App\Models\FilemangementSystem;
use App\Models\ApplicationLink;
use App\Helpers\Common;
class ApplicantController extends Controller
{
    public function showapplicantForm(Request $request,$id)
    {
        $page_title = 'Job Applicant Form';
        $source = base64_decode($request->get('source'));

        $resort_id_de = base64_decode($id);

        $get_ids = explode("/", $resort_id_de);
        $resort_id = @$get_ids['0'];
        $ta_childid = @$get_ids['1'];
        $v_id = @$get_ids['2'];

        $linkDetails = ApplicationLink::where('Resort_id', $resort_id)
                        ->where('ta_child_id', $ta_childid)
                        ->first();

        // Check if link exists and validate expiry date
        if (!$linkDetails) {
            return redirect()->back()->with('error', 'Application link not found.');
        }

        // Validate link expiry
        if ($linkDetails->link_Expiry_date) {
            $expiryDate = \Carbon\Carbon::parse($linkDetails->link_Expiry_date);
            $today = \Carbon\Carbon::now();
            
            if ($today->greaterThan($expiryDate)) {

            // Redirect to a dedicated page for expired links
            // return view('resorts.applicant_form.expired_link', [
            //     'message' => 'This application link has expired.',
            //     'linkDetails' => $linkDetails
            // ]);
            
            // Alternative approach using named route:
            // return redirect()->route('application.expired')->with('error', 'This application link has expired.');
            }
        }

        $get_vacancies = Vacancies::where('id',$get_ids['2'])->first();
        $get_questionnaire = Questionnaire::where('Resort_id',$resort_id)
                        ->where('Department_id',$get_vacancies->department)
                        ->where('Division_id',$get_vacancies->division)
                        ->where('Position_id',$get_vacancies->position)
                        ->first();
        $get_Languages = ResortLanguages::select('name','id')->get();
        $countries = Country::all();

        //question get
        $termsAndCondition = TermsAndCondition::where('resort_id', $resort_id)->first();

   	    $get_questionnaireVideo = VideoQuestion::where('Q_Parent_id',$get_questionnaire->id)->groupBy('lang_id')->get();
   	    $get_questionnaireVideoshow = VideoQuestion::where('Q_Parent_id',$get_questionnaire->id)->get();
   	    $get_questionnaireChild = QuestionnaireChild::where('Q_Parent_id',$get_questionnaire->id)->get();
   	    $levelList = config('settings.levels');
        
        $logo = Resort::where('id', $resort_id)->first('id');
        return view('resorts.applicant_form.index',compact('logo','page_title','v_id','resort_id','termsAndCondition','get_questionnaireChild','get_questionnaireVideo','get_questionnaire','get_Languages','get_questionnaireVideoshow','levelList','countries','source'));
    }

    public function saveDraft(Request $request)
    {
        try {
            $step = $request->step;
            $sessionData = $request->except('step', '_token', 'video');

            // Handle video file upload
            if ($request->hasFile('video')) {
                $video = $request->file('video');
                $path = $video->store('temp/videos', 'public'); // Save video in a temporary folder
                $sessionData['video_path'] = $path; // Save file path instead of the file object
            }

            // Retrieve existing session data
            $existingData = Session::get('applicant_form', []);
            $existingData[$step] = $sessionData;

            Session::put('applicant_form', $existingData);

            return response()->json(['success' => true, 'message' => 'Step data saved successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getDraftStepData(Request $request)
    {
        $step = $request->step; // Get the step identifier
        $sessionData = Session::get('applicant_form', []);

        if (isset($sessionData[$step])) {
            return response()->json(['success' => true, 'data' => $sessionData[$step]]);
        }

        return response()->json(['success' => false, 'message' => 'No data found for this step.']);
    }

    public function applicant_formStore(Request $request)
    {


            // Validation rules
            $validatedData = $request->validate([
                'terms_conditions' => 'required',
                'select_months' => 'nullable|required_without:select_years|integer|between:1,12',
                'select_years' => 'nullable|required_without:select_months|integer|between:1,5',
                // Add other validation rules as needed
            ]);
            $vacancy_id = $request->vacancy_id;

            // Fetch country and timezone
            $country = Country::where('id', $request->country)->first();
            $timezone = Common::getTimezoneByCountry($country->shortname);
            $applicant = new Applicant_form_data();
            $applicant->resort_id = $request->resort_id;
            $applicant->Parent_v_id = $request->vacancy_id;
            $applicant->Application_date = now();
            $applicant->passport_no = $request->passport_no;
            $applicant->passport_expiry_date = $request->passport_expiry_date;
            $applicant->first_name = $request->first_name;
            $applicant->last_name = $request->last_name;
            $applicant->gender = $request->gender;
            $applicant->dob = $request->dob;
            $applicant->mobile_number = $request->mobile_number;
            $applicant->email = $request->email;
            $applicant->marital_status = $request->marital_status;
            $applicant->number_of_children = $request->number_of_children;
            $applicant->address_line_one = $request->address_line_one;
            $applicant->address_line_two = $request->address_line_two;
            $applicant->country = $request->country;
            $applicant->state = $request->state;
            $applicant->city = $request->city;
            $applicant->pin_code = $request->pin_code;
            $applicant->Joining_availability = $request->Joining_availability;
            $applicant->reference = $request->reference;
            $applicant->terms_conditions = $request->terms_conditions;
            $applicant->data_retention_month = $request->select_months;
            $applicant->data_retention_year = $request->select_years;
            $applicant->NotiesPeriod = $request->notice_period;
            $applicant->SalaryExpectation = $request->expected_salary;
            $applicant->TimeZone = $timezone[0];
            $applicant->Applicant_Source = $request->source;
            $applicant->save(); // Save applicant data first to get ID



            // Define dynamic folder path

            $applicant_id = $applicant->id; // Get newly created applicant I
            $encoded_applicant_id = base64_encode($applicant_id);
            $resort = Resort::where('id', $request->resort_id)->first();
            $uniqueString = substr(md5(uniqid($applicant_id, true)), 0, 10);
            // $fileManagement = FilemangementSystem::where('Folder_Name',$encoded_applicant_id)->where('resort_id',$request->resort_id)->first();
           
            // if(!$fileManagement){
            //     $fileManagement = Common::createFolderByName($request->resort_id, $applicant_id, 'uncategorized');
            // }
            
            // if($fileManagement)
            // {
            //     $folderName = $fileManagement->Folder_Name;
            // }

            
            // Handle Passport Upload
            if ($request->hasFile('passport')) {
                $file = $request->file('passport');
               
            
                $aws= Common::ApplicantWiseStorefileaws($request->resort_id, $vacancy_id,$file);
                $applicant->passport_img = $aws['path']; // Save relative file path
            }

            // Handle Curriculum Vitae Upload
            if ($request->hasFile('curriculum_file')) {
                $file = $request->file('curriculum_file');
              
                $aws= Common::ApplicantWiseStorefileaws($request->resort_id, $vacancy_id,$file);
          
                $applicant->curriculum_vitae = $aws['path']; // Save relative file path
            }

            // Handle Passport Photo Upload
            if ($request->hasFile('profile_picture')) {
                $file = $request->file('profile_picture');
                $aws= Common::ApplicantWiseStorefileaws($request->resort_id, $vacancy_id,$file);
                $applicant->passport_photo = $aws['path']; // Save relative file path
            }

            // Handle Full-Length Photo Upload
            if ($request->hasFile('full_length_photo')) {
                $file = $request->file('full_length_photo');
                $aws= Common::ApplicantWiseStorefileaws($request->resort_id, $vacancy_id,$file);
                $applicant->full_length_photo = $aws['path']; // Save relative file path
            }

            // Save updated file paths
            $applicant->save();

            // Handle work experience data
            $totalExperience = 0;
            $employmentStatus = 'Available';  // Default status is "Available"

            foreach ($request->job_title as $key => $job_data) {
                if (
                    !isset($request->employer_name[$key], $request->work_country_name[$key],
                        $request->work_city[$key], $request->total_experience[$key],
                        $request->work_start_date[$key], $request->job_description_work[$key])
                ) {
                    continue; // Skip if any required field is missing
                }

                $work_experience = new Work_experience_applicant_form();
                $work_experience->applicant_form_id = $applicant->id;
                $work_experience->job_title = $job_data;
                $work_experience->employer_name = $request->employer_name[$key];
                $work_experience->work_country_name = $request->work_country_name[$key];
                $work_experience->work_city = $request->work_city[$key];
                $work_experience->total_work_exp = $request->total_experience[$key]; // Ensure parsing if needed
                $work_experience->work_start_date = \Carbon\Carbon::createFromFormat('d/m/Y', $request->work_start_date[$key])->format('Y-m-d');
                $work_experience->work_end_date = isset($request->work_end_date[$key])
                    ? \Carbon\Carbon::createFromFormat('d/m/Y', $request->work_end_date[$key])->format('Y-m-d')
                    : null;
                $work_experience->job_description_work = $request->job_description_work[$key];
                $work_experience->currently_working = $request->currently_working[$key] ?? 0;

                // Calculate total experience in months/years if numeric value is not given
                $totalExperience += (float)$request->total_experience[$key];

                if ($work_experience->currently_working == 1) {
                    $employmentStatus = 'Working'; // Set status if currently working
                }

                $work_experience->save();
            }
            // dd( $totalExperience , $employmentStatus);
            // Update total experience and employment status
            $applicant->Total_Experiance = $totalExperience;
            $applicant->employment_status = $employmentStatus;
            $applicant->save();

            // Handle education data
            foreach ($request->institute_name as $key => $education_data) {
                $education = new Education_applicant_form();
                $education->applicant_form_id = $applicant->id;
                $education->institute_name = $request->institute_name[$key];
                $education->educational_level = $request->educational_level[$key];
                $education->country_educational = $request->country_educational[$key];
                $education->city_educational = $request->city_educational[$key];
                $education->save();
            }

            // step 4 : Handle job assessment
            // dd($request->all() );
            foreach ($request->all() as $key => $value) 
            {

                if (str_starts_with($key, 'question_')) {
                    $questionId = str_replace('question_', '', $key);
                    $data = [
                        'applicant_form_id' => $applicant->id,
                        'question_id' => $questionId,
                        'question_type' => $request->get("question_type_$questionId") ?? null,
                    ];

                    try {
                        // Defensive handling of different input types
                        if (is_array($value)) {
                            $data['multiple_responses'] = json_encode($value);
                            $data['response'] = null;
                        } elseif (is_scalar($value)) {
                            $data['response'] = (string)$value;
                            $data['multiple_responses'] = null;
                        } else {
                            $data['response'] = null;
                            $data['multiple_responses'] = null;
                        }
                        Applicant_form_job_assessment::create($data);
                    } catch (\Exception $e) {
                        \Log::error('Job Assessment Creation Error', [
                            'data' => $data,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                if (str_starts_with($key, 'tempVideoReference_')) {
                    $questionId = str_replace('tempVideoReference_', '', $key);
                    // $questionId = str_replace($questionId, '', 'video_questionid_');
                     $tempVideo = Temp_language_video_store::find($value); // Use video ID from the request
                    if ($tempVideo) {
                        $videoLanguage = $request->get("language_$key");
                        $extension = pathinfo($tempVideo->video, PATHINFO_EXTENSION);
                        $fileName = "{$videoLanguage}_test_video.{$extension}";
                        $sourcePath = public_path($tempVideo->video);

                        // Upload to AWS using your helper
                        $awsResult = Common::ApplicantWiseStorefileaws($request->resort_id, $vacancy_id,$file);

                        if (!empty($awsResult['path'])) {
                            Applicant_form_job_assessment::create([
                                'applicant_form_id' => $applicant->id,
                                'question_id' => $request['video_questionid_'.$questionId],
                                'question_type' => 'video',
                                'video_language_test' => $videoLanguage,
                                'video_path' => $awsResult['path'],
                            ]);
                            // Delete the temporary video record after uploading
                            $tempVideo->delete();
                            // Optionally, delete the local file
                            if (file_exists($sourcePath)) {
                                @unlink($sourcePath);
                            }
                        } else {
                            \Log::error("AWS upload failed for video: $sourcePath");
                        }
                    }
                }
                // if (str_starts_with($key, 'tempVideoReference_')) {
                //     $questionId = str_replace('tempVideoReference_', '', $key);
                //     // $questionId = str_replace($questionId, '', 'video_questionid_');
                //     $tempVideo = Temp_language_video_store::find($value); // Use video ID from the request
                //     // dd($tempVideo);
                //     if ($tempVideo) {
                //         $videoLanguage = $request->get("language_$key");
                //         $extension = pathinfo($tempVideo->video, PATHINFO_EXTENSION);
                //         $fileName = "{$videoLanguage}_test_video.{$extension}";
                //         $sourcePath = public_path($tempVideo->video);
                //         // Ensure dynamic path is properly formatted
                //         $dynamic_path = rtrim($dynamic_path, '/');

                //         // File name (from source path)
                //         $fileName = basename($sourcePath); // Extracts "video_67528e1c23bca8.57595350.webm"

                //         // Destination file path
                //         $destinationPath = $dynamic_path . '/' . $fileName;
                //         // dd($destinationPath);
                //         try {
                //             // Ensure the destination folder exists
                //             if (!file_exists($dynamic_path)) {
                //                 if (!mkdir($dynamic_path, 0755, true) && !is_dir($dynamic_path)) {
                //                     throw new Exception("Failed to create directory: $dynamic_path");
                //                 }
                //             }

                //             // Move the file
                //             if (copy($sourcePath, $destinationPath)) {
                //                 \Log::info("File successfully moved to: $destinationPath");
                //                 // Save the record in your database (if necessary)
                //                 Applicant_form_job_assessment::create([
                //                     'applicant_form_id' => $applicant->id,
                //                     'question_id' => $request['video_questionid_'.$questionId],
                //                     'question_type' => 'video',
                //                     'video_language_test' => $videoLanguage,
                //                     'video_path' => "{$dynamic_path}/{$fileName}",
                //                 ]);
                //                 // Delete the temporary video record after moving
                //                 $tempVideo->delete();
                //             } else {
                //                 throw new Exception("Failed to copy the file from $sourcePath to $destinationPath");
                //             }
                //         } catch (Exception $e) {
                //             \Log::error("Error moving file: " . $e->getMessage());
                //             echo "Error: " . $e->getMessage();
                //         }
                //     }
                // }

                if (str_starts_with($key, 'video_file_')) {
                    $questionId = str_replace('tempVideoReference_', '', $key);
                    $tempVideo = Temp_language_video_store::find($value); // Use video ID from the request
                    if ($tempVideo) {
                        $videoLanguage = $request->get("language_$key");
                        $file = $request->file($key);
                        $extension = pathinfo($tempVideo->video, PATHINFO_EXTENSION);
                        $fileName = "{$videoLanguage}_test_video.{$extension}";
                        $sourcePath = public_path(config('settings.Resort_Applicant') . '/' . $tempVideo->video);
                        
                        $awsResult = Common::AWSEmployeeFileUpload($request->resort_id, $sourcePath, $folderName);
                        if (!empty($awsResult['path'])) {
                            $res = Applicant_form_job_assessment::create([
                                'applicant_form_id' => $applicant->id,
                                'question_id' => $request['video_questionid_'.$questionId],
                                'question_type' => 'video',
                                'video_language_test' => $videoLanguage,
                                'video_path' => $awsResult['path'],
                            ]);
                            // Delete temporary record
                            $tempVideo->delete();
                        }
                    }
                }


                // if (str_starts_with($key, 'video_file_')) {
                //     $questionId = str_replace('tempVideoReference_', '', $key);
                //     $tempVideo = Temp_language_video_store::find($value); // Use video ID from the request
                //     if ($tempVideo) {
                //         $videoLanguage = $request->get("language_$key");
                //         $file = $request->file($key);
                //         $extension = pathinfo($tempVideo->video, PATHINFO_EXTENSION);
                //         $fileName = "{$videoLanguage}_test_video.{$extension}";
                //         $sourcePath = public_path(config('settings.Resort_Applicant') . '/' . $tempVideo->video);
                //         Common::uploadFile($sourcePath, $fileName, $dynamic_path);
                //         $res = Applicant_form_job_assessment::create([
                //             'applicant_form_id' => $applicant->id,
                //             'question_id' => $request['video_questionid_'.$questionId],
                //             'question_type' => 'video',
                //             'video_language_test' => $videoLanguage,
                //             'video_path' => "{$dynamic_path}/{$fileName}",
                //         ]);
                //         // Delete temporary record
                //         $tempVideo->delete();
                //     }
                // }
            }

            //step 5 :  Handle language proficiency data
            if ($request->has('select_level')) {

                foreach ($request->select_level as $key => $language) {
                    ApplicantLanguage::create([
                        'applicant_form_id' => $applicant->id,
                        'language' => array_key_exists($key, $request->preliminary_language) ? $request->preliminary_language[$key]:16,
                        'level' => $language ,
                    ]);
                }
            }

            // Create applicant status
            ApplicantWiseStatus::create([
                'Applicant_id' => $applicant->id,
                'As_ApprovedBy' => 0,
                'status' => 'Sortlisted By Wisdom AI',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => 'Application submitted successfully!',
                'application_id' => $applicant->id,
            ]);


        DB::beginTransaction();

        try {  } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Job Assessment Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Failed to submit application. Please try again.',
                'error' => $e->getMessage(),
            ]);
        }
    }


    public function applicant_tempVideoloadfile(Request $request)
    {
    	$html = view('resorts.applicant_form.modal-content')->with(compact('request'))->render();
        return $html;
    }

    public function applicant_tempVideoStore(Request $request)
    {
        \Log::info('Request received:', $request->all());

        // $request->validate([
        //     'video' => 'required|mimes:mp4,webm,ogg,mkv,tmp|max:50000',
        // ]);
        
        // if (!$request->hasFile('video')) {
        //     Log::error('Video file is missing');
        //     return response()->json(['message' => 'No video file uploaded'], 400);
        // }
        // Process file upload


        try
        {
            $ipAddress = $request->ip();
            $systemInfo = [
                'os' => php_uname(), // Operating System details
                'ipAddress' => $ipAddress,
            ];

            $videoPath = config('settings.Resort_Applicant'); // Base path

            // // Ensure directory exists
            // if (!file_exists(public_path($videoPath))) {
            //     mkdir(public_path($videoPath), 0755, true);
            // }

            
            // Store the uploaded file
            // if ($request->hasFile('video')) {
                $fileName = uniqid('video_', true) . '.' . $request->video->getClientOriginalExtension();

                // Common::uploadFile($request->video, $fileName, $videoPath);

                $vacancy_id =  $request->vacancy_id;
                $status = Common::TalentAcquisitionFolder($request->resort_id,$vacancy_id,$request->video);
          
                if($status == true)
                {
                    $tempData = Temp_language_video_store::create([
                        'resort_id' => $request->resort_id,
                        'video' =>$status['path'],
                        'os' => $systemInfo['os'],
                        'ipAddress' => $systemInfo['ipAddress'],
                    ]);

                    return response()->json([
                        'message' => 'Video uploaded successfully!',
                        'path' => $status['path'],
                        'video_id' => $tempData->id,
                    ]);
                }
                else
                {
                    return response()->json(['message' => 'Failed to create folder'], 500);
                }
                


            return response()->json(['message' => 'Failed to upload video'], 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed:', $e->errors());
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }
    }

    public function applicant_tempVideoremove(Request $request)
    {
    	$remove_data = Temp_language_video_store::find('1')->delete();
    }

}
