<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\Admin;
use App\Models\Resort;
use App\Models\Division;
use App\Models\Department;
use App\Models\Section;
use App\Models\Position;
use App\Models\Settings;
use App\Models\Role;
use App\Helpers\Common;
use Carbon\Carbon;
use DB;

class AdminDashboardController extends Controller
{
  public function dashboard()
  {
    try {
      $admin = Auth::guard('admin')->user();
      $adminCount = Admin::where('status','active')->count();
      $roleCount = Role::where('status','active')->count();
      $resortsCount = Resort::where('status','active')->count();

      $divisionsCount = Division::all()->count();
      $departmentsCount = Department::all()->count();
      $sectionsCount = Section::all()->count();
      $positionsCount = Position::all()->count();

      return view('admin.dashboard.admin')->with( compact
        (
          'admin',
          'adminCount',
          'roleCount',
          'resortsCount',
          'divisionsCount',
          'departmentsCount',
          'sectionsCount',
          'positionsCount'
        )
      );
    } catch( \Exception $e ) {
      \Log::emergency("File: ".$e->getFile());
      \Log::emergency("Line: ".$e->getLine());
      \Log::emergency("Message: ".$e->getMessage());
    }
  }
}
