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
use App\Models\ResortPosition;
use App\Models\Division;
use App\Models\Department;
use App\Models\Position;
use App\Helpers\Common;

class PositionController extends Controller
{
    public function list(Request $request)
    {
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;

        if ($request->ajax()) {
           
            $sections = ResortPosition::select([
                'resort_positions.id', 
                'resort_positions.dept_id', 
                'resort_departments.name as department_name', 
                'resort_positions.name', 
                'resort_positions.code', 
                'resort_positions.short_title', 
                'resort_positions.status', 
                'resort_positions.created_by', 
                'resort_divisions.name as division', 
                'resort_positions.created_at', 
                'resort_positions.updated_at'
            ])
            ->join('resort_departments', 'resort_positions.dept_id', '=', 'resort_departments.id')
            ->join('resort_divisions', 'resort_divisions.id', '=', 'resort_departments.division_id')
            ->where('resort_positions.resort_id',$resort_id)
            ->orderBy('resort_positions.created_at', 'DESC')
            ->get();

            return datatables()->of($sections)
                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-success btn-sm edit-division" data-id="' . $row->id . '">Edit</button>
                        <button class="btn btn-danger btn-sm delete-division" data-id="' . $row->id . '">Delete</button>
                    ';
                })
                ->editColumn('position_title', function ($row) {
                    return '<input type="text" class="inline-edit" data-id="' . $row->id . '" data-field="position_title" value="' . $row->position_title . '">';
                })
                ->editColumn('division', function ($row) {
                    return '<input type="text" class="inline-edit" data-id="' . $row->id . '" data-field="division" value="' . $row->division . '">';
                })
                ->editColumn('department_name', function ($row) {
                    return '<input type="text" class="inline-edit" data-id="' . $row->id . '" data-field="department_name" value="' . $row->department_name . '">';
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
                ->rawColumns(['position_title', 'division','department_name','code', 'short_name', 'status', 'action'])
                ->make(true);
        }
        return view('resorts.manning.index');
    }

    public function create()
    {
        // Fetch the current resort's divisions
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;// Assuming the user is authenticated as a resort admin
        $positions = Position::where('status', 'active')->get();
        $departments = Department::where('status', 'active')->get(); // Fetching divisions for the current resort

        return view('resorts.manning.index', compact('positions','resort_id','departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'dept_id' => 'required|integer',
            'position_title' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'short_name' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
        ]);

        $position = new ResortPosition();
        $position->resort_id = Auth::guard('resort-admin')->user()->resort_id;
        $position->dept_id = $request->dept_id;
        $position->position_title = $request->position_title;
        $position->code = $request->code;
        $position->short_name = $request->short_name;
        $position->status = $request->status;
        $position->save();

        return response()->json(['success' => true]);
    }

    public function inlineUpdate(Request $request)
    {
        $position = ResortPosition::find($request->id);

        if ($position) {
            $position->{$request->field} = $request->value;
            $position->save();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Position not found.']);
    }

    public function destroy($id)
    {
        $position = ResortPosition::find($id);

        if ($position) {
            $position->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Position not found.']);
    }
}