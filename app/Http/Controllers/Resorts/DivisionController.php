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
use App\Models\Division;
use App\Helpers\Common;



class DivisionController extends Controller
{
    public function list(Request $request)
    {
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;

        if ($request->ajax()) {
            $divisions = ResortDivision::where('resort_id', $resort_id)->orderBy('created_at', 'DESC')->get();

            return datatables()->of($divisions)
                ->addColumn('action', function ($row) {
                    return '
                        <button class="btn btn-success btn-sm edit-division" data-id="' . $row->id . '">Edit</button>
                        <button class="btn btn-danger btn-sm delete-division" data-id="' . $row->id . '">Delete</button>
                    ';
                })
                ->editColumn('name', function ($row) {
                    return '<input type="text" class="inline-edit" data-id="' . $row->id . '" data-field="name" value="' . $row->name . '">';
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
                ->rawColumns(['name', 'code', 'short_name', 'status', 'action'])
                ->make(true);
        }

        return view('resorts.manning.index');
    }

    public function create()
    {
        // Fetch the current resort's divisions
        $resortId = Auth::user()->resort_id;  // Assuming the user is authenticated as a resort admin
        $divisions = Division::where('status', 'active')->get(); // Fetching divisions for the current resort

        return view('resorts.manning.index', compact('divisions','resortId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'short_name' => 'required|string|max:50',
            'status' => 'required|in:active,inactive',
        ]);

        $division = new ResortDivision();
        $division->resort_id = Auth::guard('resort-admin')->user()->resort_id;
        $division->name = $request->name;
        $division->code = $request->code;
        $division->short_name = $request->short_name;
        $division->status = $request->status;
        $division->save();

        return response()->json(['success' => true]);
    }


    public function inlineUpdate(Request $request)
    {
        $division = ResortDivision::find($request->id);

        if ($division) {
            $division->{$request->field} = $request->value;
            $division->save();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Division not found.']);
    }

    public function destroy($id)
    {
        $division = ResortDivision::find($id);

        if ($division) {
            $division->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Division not found.']);
    }




}
