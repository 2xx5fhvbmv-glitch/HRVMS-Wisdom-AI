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
use App\Models\ResortSection;
use App\Models\Division;
use App\Models\Department;
use App\Models\Section;
use App\Helpers\Common;

class SectionController extends Controller
{
    public function list(Request $request)
    {
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;

        if ($request->ajax()) {
           
            $sections = ResortSection::select([
                'resort_sections.id', 
                'resort_sections.dept_id', 
                'resort_departments.name as department_name', 
                'resort_sections.name', 
                'resort_sections.code', 
                'resort_sections.short_title', 
                'resort_sections.status', 
                'resort_sections.created_by', 
                'resort_divisions.name as division', 
                'resort_sections.created_at', 
                'resort_sections.updated_at'
            ])
            ->join('resort_departments', 'resort_sections.dept_id', '=', 'resort_departments.id')
            ->join('resort_divisions', 'resort_divisions.id', '=', 'resort_departments.division_id')
            ->where('resort_sections.resort_id',$resort_id)
            ->orderBy('resort_sections.created_at', 'DESC')
            ->get();

            return datatables()->of($sections)
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
                ->rawColumns(['name', 'division','department_name','code', 'short_name', 'status', 'action'])
                ->make(true);
        }

        return view('resorts.manning.index');
    }

    public function create()
    {
        // Fetch the current resort's divisions
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;// Assuming the user is authenticated as a resort admin
        $sections = Sections::where('status', 'active')->get();
        $departments = Department::where('status', 'active')->get(); // Fetching divisions for the current resort

        return view('resorts.manning.index', compact('sections','resort_id','departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'dept_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'short_name' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
        ]);

        $section = new ResortSection();
        $section->resort_id = Auth::guard('resort-admin')->user()->resort_id;
        $section->dept_id = $request->dept_id;
        $section->name = $request->name;
        $section->code = $request->code;
        $section->short_name = $request->short_name;
        $section->status = $request->status;
        $section->save();

        return response()->json(['success' => true]);
    }

    public function inlineUpdate(Request $request)
    {
        $section = ResortSection::find($request->id);

        if ($section) {
            $section->{$request->field} = $request->value;
            $section->save();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Section not found.']);
    }

    public function destroy($id)
    {
        $section = ResortSection::find($id);

        if ($section) {
            $section->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Section not found.']);
    }
}