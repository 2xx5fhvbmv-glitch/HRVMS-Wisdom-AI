<?php
namespace App\Http\Controllers\Resorts;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use App\Models\ResortAdmin;
use App\Models\ResortRole;
use App\Models\ResortModule;
use App\Models\ResortPermission;
use App\Models\ResortModulePermission;
use App\Models\ResortRoleModulePermission;
use App\Models\ResortBudgetCost;
use App\Helpers\Common;
class ResortsSitesttingsController extends Controller
{

    public function index()
    {
        return view('resorts.sitesttings.index');
    }

}
