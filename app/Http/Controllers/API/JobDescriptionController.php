<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\JobDescriptionEmployeeRecord;
use App\Helpers\Common;
use App\Helpers\StorageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

/**
 * Mobile side of Part 2.5 — an employee's own view of the job descriptions
 * issued to them, plus consent/decline. The web-portal controller
 * (Resorts\TalentAcquisition\JobDescriptionController) owns issuance;
 * this one only ever touches the current employee's own records.
 */
class JobDescriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        $employee = Auth::guard('api')->user()->GetEmployee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 200);
        }

        $records = JobDescriptionEmployeeRecord::where('employee_id', $employee->id)
            ->orderBy('id', 'DESC')
            ->get(['id', 'status', 'sent_at', 'signed_at', 'decline_reason']);

        return response()->json(['success' => true, 'data' => $records]);
    }

    public function show($id)
    {
        $employee = Auth::guard('api')->user()->GetEmployee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 200);
        }

        $record = JobDescriptionEmployeeRecord::where('employee_id', $employee->id)->find($id);
        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Job description not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $record,
            'pdf_url' => $record->pdf_path ? StorageHelper::temporaryUrl($record->pdf_path, 30) : null,
        ]);
    }

    /**
     * Snapshots the employee's own signature (same source as their profile
     * signature — ResortAdmin.signature_img via Admin_Parent_id) onto the
     * record, marks it Signed, and re-renders the PDF with both signatures
     * now present.
     */
    public function consent($id)
    {
        $employee = Auth::guard('api')->user()->GetEmployee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 200);
        }

        $record = JobDescriptionEmployeeRecord::where('employee_id', $employee->id)->find($id);
        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Job description not found'], 404);
        }
        if ($record->status !== 'Pending') {
            return response()->json(['success' => false, 'message' => 'This job description has already been actioned.'], 422);
        }
        if (empty($employee->Admin_Parent_id)) {
            return response()->json(['success' => false, 'message' => 'No signature on file. Please add your signature in your profile first.'], 422);
        }

        $signature = Common::snapshotSignature($employee->Admin_Parent_id, 'job_description', $record->id);
        if (empty($signature['signature_img'])) {
            return response()->json(['success' => false, 'message' => 'No signature on file. Please add your signature in your profile first.'], 422);
        }

        $record->employee_signature_path = $signature['signature_img'];
        $record->status = 'Signed';
        $record->signed_at = now();
        $record->save();

        Common::generateAndStoreJobDescriptionPdf($record);

        return response()->json(['success' => true, 'message' => 'Job description signed.']);
    }

    /**
     * The reverse side of "Job Description Ready for Review" — HR gets
     * notified on decline too, not just silence (CLAUDE.md's most common
     * notification gap in this codebase).
     */
    public function decline(Request $request, $id)
    {
        $employee = Auth::guard('api')->user()->GetEmployee;
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 200);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        $record = JobDescriptionEmployeeRecord::where('employee_id', $employee->id)->find($id);
        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Job description not found'], 404);
        }
        if ($record->status !== 'Pending') {
            return response()->json(['success' => false, 'message' => 'This job description has already been actioned.'], 422);
        }

        $record->status = 'Declined';
        $record->decline_reason = $request->reason;
        $record->save();

        $hrEmployeeIds = array_values(array_diff(Common::getResortHrEmployeeIds($record->resort_id), [$employee->id]));
        if (!empty($hrEmployeeIds)) {
            // employees has no first_name of its own — that identity lives
            // on the linked resort_admins row (Admin_Parent_id).
            $declinerName = optional($employee->resortAdmin)->first_name ?: $employee->Emp_id;
            Common::notifyEmployees(
                $record->resort_id,
                $hrEmployeeIds,
                'Job Description Declined',
                trim($declinerName . ' declined their job description: ' . $request->reason),
                'TalentAcquisition',
                $record->id,
                'job-description-employee-record'
            );
        }

        return response()->json(['success' => true, 'message' => 'Job description declined.']);
    }
}
