<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Employee; // Ensure you import your model
use App\Models\EmployeesDocument; // Ensure you import your model
use App\Models\ResortAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Common;
use Validator;

class EmployeeDocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api'); // This will protect all methods in this controller
    }

    public function employeeDocument(Request $request)
    {
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'document_title' => 'required',
            'document_path' => 'required|file|mimes:xls,xlsx,pdf,jpg,jpeg,png',
            'document_category' => 'required',
        ],
            [
            'document_path.mimes' => 'The Benifit Grid file must be a type of: xls, xlsx ,pdf,jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
       
        try {
            $user = Auth::guard('api')->user();
            $employee = $user->GetEmployee;
            $resortId = $user->resort_id;

            $employeesDoc = new EmployeesDocument();
            $employeesDoc->employee_id = $employee->id;
            $employeesDoc->resort_id = $resortId;
            $employeesDoc->document_title = $request->document_title;
            $employeesDoc->document_category = $request->document_category;
            $employeesDoc->save();

            $getEmp_id = Employee::find($employeesDoc->employee_id);
            // Handle Passport Upload
            if ($request->hasFile('document_path')) {
                $file           =   $request->file('document_path');
                $fileName       =   $file->getClientOriginalName();
                $fileSize       =   $file->getSize();
                $formattedSize  =   $this->formatFileSize($fileSize);
                $employeesDoc->document_file_size =   $formattedSize;
                $SubFolder      =   "EmployeesDocument";
                $status         =   Common::AWSEmployeeFileUpload($user->resort_id,$file, $getEmp_id->Emp_id,$SubFolder,true);

                if ($status['status'] == false) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'File upload failed: ' . ($status['msg'] ?? 'Unknown error')
                    ], 400);
                } else {
                    if($status['status'] == true && isset($status['Chil_file_id']) && !empty($status['Chil_file_id'])) {
                        $filename = $file->getClientOriginalName();
                        $filePath = ['Filename' => $filename, 'Child_id' => $status['Chil_file_id']];
                    }
                }

                $employeesDoc->document_path = $filePath ? json_encode($filePath) : null; // Save relative file path
            }
            // Save updated file paths
            $employeesDoc->save();

            if (!$employeesDoc) {
                return response()->json(['success' => false, 'message' => 'File not uploaded'], 200);
            }
            return response()->json(['success' => true, 'message' => 'File upload succesfully'],200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::error($e->getMessage());
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
        
    }

    public function getEmployeeDocument(Request $request)
    {
        // Check if the user is authenticated
        if (!Auth::guard('api')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'document_category' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            $user = Auth::guard('api')->user();
            $resortId = $user->resort_id;

            // Fetch employee documents based on filters
            $employeesDoc = EmployeesDocument::where('resort_id', $resortId)
                ->where('document_category', $request->document_category)
                ->get();

            // Check if no documents were found
            if ($employeesDoc->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No documents found'], 200);
            }

             // Append base URL to document_path
            $baseUrl = url('/'); // Get the base URL of the application

            foreach ($employeesDoc as $doc) {
                $doc->document_path = $baseUrl . '/' . $doc->document_path;
            }

            // Return the documents
            return response()->json(['success' => true, 'data' => $employeesDoc], 200);

        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            // Log the exception for debugging
            \Log::error('Error fetching employee documents: ' . $e->getMessage());

            // Return a server error response
            return response()->json(['success' => false, 'message' => 'Server error'], 500);
        }
    }

    function formatFileSize($bytes)
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}

?>