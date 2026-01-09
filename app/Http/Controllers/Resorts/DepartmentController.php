<?php

namespace App\Http\Controllers\Resorts;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use DB;
use BrowserDetect;
use Route;
use File;
use App\Models\ResortAdmin;
use App\Models\ResortDivision;
use App\Models\ResortDepartment;
use App\Models\Division;
use App\Models\Department;
use App\Helpers\Common;

class DepartmentController extends Controller
{
    public function list(Request $request)
    {
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;

        if ($request->ajax()) {

            $departments = ResortDepartment::select([
                'resort_departments.id',
                'resort_divisions.name as division',
                'resort_departments.code',
                'resort_departments.name',
                'resort_departments.short_name',
                'resort_departments.status',
                'resort_departments.created_by',
                'resort_departments.created_at',
                'resort_departments.updated_at'
            ])
            ->join('resort_divisions', 'resort_departments.division_id', '=', 'resort_divisions.id')
            ->where('resort_departments.resort_id',$resort_id)
            ->orderBy('resort_departments.created_at', 'DESC')
            ->get();

            return datatables()->of($departments)
                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-success btn-sm edit-division" data-id="' . $row->id . '">Edit</button>
                        <button class="btn btn-danger btn-sm delete-division" data-id="' . $row->id . '">Delete</button>
                    ';
                })
                ->editColumn('name', function ($row) {
                    return '<input type="text" class="inline-edit" data-id="' . $row->id . '" data-field="name" value="' . $row->name . '">';
                })
                ->editColumn('division', function ($row) {
                    return '<input type="text" class="inline-edit" data-id="' . $row->id . '" data-field="division" value="' . $row->division . '">';
                })
                ->editColumn('code', function ($row) {
                    return '<input type="text" class="inline-edit" data-id="' . $row->id . '" data-field="code" value="' . $row->code . '">';
                })
                ->editColumn('short_name', function ($row) {
                    return '<input type="text" class="inline-edit" data-id="' . $row->id . '" data-field="short_name" value="' . $row->short_name . '">';
                })
                ->editColumn('status', function ($row) {
                    return '
                        <select class="inline-edit" data-id="' . $row->id . '" data-field="status">
                            <option value="active" ' . ($row->status == "active" ? 'selected' : '') . '>Active</option>
                            <option value="inactive" ' . ($row->status == "inactive" ? 'selected' : '') . '>Inactive</option>
                        </select>
                    ';
                })
                ->rawColumns(['name', 'division','code', 'short_name', 'status', 'action'])
                ->make(true);
        }

        return view('resorts.manning.index');
    }

    public function create()
    {
        // Fetch the current resort's divisions
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;// Assuming the user is authenticated as a resort admin
        $divisions = Division::where('status', 'active')->get();
        $departments = Department::where('status', 'active')->get(); // Fetching divisions for the current resort

        return view('resorts.manning.index', compact('divisions','resort_id','departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'division_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'short_name' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
        ]);

        $department = new ResortDepartment();
        $department->resort_id = Auth::guard('resort-admin')->user()->resort_id;
        $department->division_id = $request->division_id;
        $department->name = $request->name;
        $department->code = $request->code;
        $department->short_name = $request->short_name;
        $department->status = $request->status;
        $department->save();

        return response()->json(['success' => true]);
    }

    public function inlineUpdate(Request $request)
    {
        $department = ResortDepartment::find($request->id);

        if ($department) {
            $department->{$request->field} = $request->value;
            $department->save();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Department not found.']);
    }

    public function destroy($id)
    {
        $department = ResortDepartment::find($id);

        if ($department) {
            $department->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Department not found.']);
    }
}
