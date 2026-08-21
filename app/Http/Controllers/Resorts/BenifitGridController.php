<?php

namespace App\Http\Controllers\Resorts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\AccommodationType;
use Illuminate\Support\Facades\Auth;
use App\Models\ResortAdmin;
use App\Models\ResortRole;
use App\Models\ResortModule;
use App\Models\ResortPermission;
use App\Models\ResortModulePermission;
use App\Models\ResortRoleModulePermission;
use App\Models\ResortBenifitGrid;
use App\Models\ResortBenefitGradeLevel;
use App\Models\ResortBenefitGradeLevelRank;
use App\Models\CustomLeave;
use App\Models\CustomBenefit;
use App\Models\CustomDiscount;
use App\Models\LeaveCategory;
use App\Models\ResortBenifitGridChild;
use App\Helpers\Common;
use App\Models\ResortSiteSettings;
use App\Models\Resort;
use DB;
use Carbon\Carbon;
use DataTables;
class BenifitGridController extends Controller
{
    public function index()
    {
        if(Common::checkRouteWisePermission('resort.benifitgrid.index',config('settings.resort_permissions.view')) == false){
                return abort(403, 'Unauthorized access');
            }
        $page_title = 'Benifit Grid';
        $resort_id = Auth::guard('resort-admin')->user()->resort_id;

        return view('resorts.benifitgrid.index')->with(
            compact(
            'page_title',
            'resort_id'
            )
        );
    }

    public function list(Request $request)
    {
        if ($request->ajax()) 
        {
            $resort_id = Auth::guard('resort-admin')->user()->resort_id;
            $gradeNames = ResortBenefitGradeLevel::where('resort_id', $resort_id)->pluck('name', 'id');

            $query = ResortBenifitGrid::where('resort_id', $resort_id);

            $edit_class = '';
            if(Common::checkRouteWisePermission('resort.benifitgrid.index',config('settings.resort_permissions.edit')) == false){
                $edit_class = 'd-none';
            }

            return DataTables::eloquent($query)
                ->addColumn('emp_grade', function ($data) use ($gradeNames) {
                    return $gradeNames[$data->emp_grade] ?? 'Unknown';
                })
                ->orderColumn('emp_grade', function ($query, $order) {
                    $query->orderBy('emp_grade', $order);
                })
                ->editColumn('effective_date', function ($data) {
                    return $formattedDate = Carbon::parse($data->effective_date)->format('d M Y');
                 })
                ->orderColumn('effective_date', function ($query, $order) {
                    $query->orderBy('effective_date', $order);
                })
                ->editColumn('status', function ($data) {
                    return ucfirst($data->status);
                })
                ->orderColumn('status', function ($query, $order) {
                    $query->orderBy('status', $order);
                })

                ->addColumn('action', function ($data) use ($edit_class) {
                    $edit_url = route('resort.benifitgrid.edit', $data->id);
                    $view_url = route('resort.benifitgrid.view', $data->id);
                    $download_pdf = route('resort.benefitgrid.pdf', $data->id);

                    return '
                        <div class="d-flex align-items-center">
                            <a href="' . $edit_url . '" class="btn-lg-icon icon-bg-green me-1 edit-row-btn '.$edit_class.'">
                                <img src="' . asset('resorts_assets/images/edit.svg') . '" alt="" class="img-fluid" />
                            </a>
                            <a href="' . $view_url . '" class="btn-tableIcon btnIcon-orange">
                            <i class="fa-regular fa-eye"></i>
                            </a>
                            <a href="' . $download_pdf . '" class="btn-tableIcon btnIcon-primary">
                            <i class="fa-regular fa-file-pdf"></i>
                            </a>
                        </div>';
                })
                ->rawColumns(['action']) // Allow HTML rendering in the 'action' column
                ->make(true);
        }
    }
    public function create()
    {
        if(Common::checkRouteWisePermission('resort.benifitgrid.index',config('settings.resort_permissions.create')) == false){
            return abort(403, 'Unauthorized access');
        }
        try {
            // Set up the page and form details
            $page_title = 'Create Benefit Grid';
            $resort_id = Auth::guard('resort-admin')->user()->resort_id;
            // $LeaveCategories = LeaveCategory::where('resort_id',$resort_id)->get();
            $excludedLeaveTypes = ['Absent', 'Present'];
            $LeaveCategories = LeaveCategory::where('resort_id',$resort_id)
            ->whereNotIn('leave_type', $excludedLeaveTypes)->get();
            // Sports options to be selected in the form
            $sports = ["Billiard", "Football", "Volleyball", "Fishing trips", "Table tennis", "Beach access", "Karaoke", "Staff gym", "Outdoor cinema", "Fifa & PubG tournaments"];
            $accomodation_type = AccommodationType::where('resort_id', $resort_id)->get();
            // Creating a new benefit grid, passing an empty model for form binding
            $benefit_grid = new ResortBenifitGrid();
            $currentGradeName = '';
            $currentGradeRanks = [];
            $rankConfig = config('settings.Position_Rank');

            // Initializing arrays for form selection (empty for create)
            $selected_linen_array = [];
            $selected_laundry = [];
            $selected_sports = [];
            $custom_leave = []; // Empty for create
            $custom_benefits = [];
            $custom_discounts = [];
            // Indicating that the form is in create mode
            $isViewMode = false;
            $custom_fields = [];
            return view('resorts.benifitgrid.edit', compact(
                'page_title', 'resort_id', 'sports', 'accomodation_type','isViewMode','currentGradeName','currentGradeRanks','rankConfig', 'benefit_grid', 'selected_linen_array', 'selected_laundry', 'selected_sports','custom_leave','custom_benefits','custom_discounts','custom_fields','LeaveCategories'
            ));
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());

            // Handle the error gracefully
            return response()->json([
                'success' => false,
                'msg' => __('messages.errorOccurred', ['name' => 'Benefit Grid'])
            ]);

        }
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $resort_id = Auth::guard('resort-admin')->user()->resort_id;

            // Employee Grade is a free-text name (e.g. "HOD L1") — resolve
            // it to the resort's matching grade-level row, creating one if
            // this name hasn't been used before.
            $gradeLevel = ResortBenefitGradeLevel::firstOrCreate(
                ['resort_id' => $resort_id, 'name' => trim($request->emp_grade)],
                ['status' => 'active']
            );
            $empGrade = (string) $gradeLevel->id;

            // Rank(s) this grade applies to are submitted right here on the
            // same form — same "one rank belongs to at most one active
            // grade" rule as the standalone config screen, reusing the
            // exact same assignment logic so the two never drift apart.
            $rankArray = array_map('intval', $request->input('ranks', []));
            ResortBenefitGradeLevelRank::assignRanksToGrade($resort_id, $gradeLevel->id, $rankArray);

            // ✅ Process custom fields
            $customFields = [];
            if ($request->has('custom_field_names') && $request->has('custom_field_values')) {
                foreach ($request->input('custom_field_names') as $index => $name) {
                    if (!empty($name)) {
                        $customFields[] = [
                            'name' => trim($name),
                            'value' => trim($request->input('custom_field_values')[$index]),
                        ];
                    }
                }
            }

            // ✅ Store benefit grid
            $benefitgrid = ResortBenifitGrid::updateOrCreate(
                ["resort_id" => $resort_id, "emp_grade" => $empGrade],
                [
                    'resort_id' => $resort_id,
                    'emp_grade' => $empGrade,
                    'rank' => implode(",", $rankArray),
                    'contract_status' => $request->contract_status,
                    'effective_date' => $request->effective_date,
                    'salary_period' => $request->salary_period,
                    'service_charge' => $request->service_charge,
                    'ramadan_bonus' => $request->ramadan_bonus,
                    'ramadan_bonus_eligibility' => $request->ramadan_bonus_eligibility,
                    'uniform' => $request->uniform,
                    'health_care_insurance' => $request->health_care_insurance,
                    'day_off_per_week' => $request->day_off_per_week,
                    'working_hrs_per_week' => $request->working_hrs_per_week, // ✅ fixed
                    'public_holiday_per_year' => $request->public_holiday_per_year,
                    'paid_worked_public_holiday_and_friday' => $request->paid_worked_public_holiday_and_friday,
                    'relocation_ticket' => $request->relocation_ticket,
                    'max_excess_luggage_relocation_expense' => $request->max_excess_luggage_relocation_expense,
                    'ticket_upon_termination' => $request->ticket_upon_termination,
                    'meals_per_day' => $request->meals_per_day,
                    'accommodation_status' => $request->accommodation_status,
                    'furniture_and_fixtures' => $request->furniture_and_fixtures,
                    'housekeeping' => $request->housekeeping,
                    'linen' => !empty($request->linen) ? implode(",", $request->linen) : "",
                    'laundry' => !empty($request->laundry) ? implode(",", $request->laundry) : "",
                    'internet_access' => $request->internet_access,
                    'telephone' => $request->telephone,
                    'annual_leave_ticket' => $request->annual_leave_ticket,
                    'overtime' => $request->overtime,
                    'salary_paid_in' => $request->salary_paid_in,
                    'loan_and_salary_advanced' => $request->loan_and_salary_advanced,
                    'sports_and_entertainment_facilities' => !empty($request->sports_and_entertainment_facilities) ? implode(",", $request->sports_and_entertainment_facilities) : "",
                    'free_return_flight_to_male_per_year' => $request->free_return_flight_to_male_per_year,
                    'food_and_beverages_discount' => $request->food_and_beverages_discount,
                    'alchoholic_beverages_discount' => $request->alchoholic_beverages_discount,
                    'spa_discount' => $request->spa_discount,
                    'dive_center_discount' => $request->dive_center_discount,
                    'water_sports_discount' => $request->water_sports_discount,
                    'friends_with_benefit_discount' => $request->friends_with_benefit_discount,
                    'standard_staff_rate_for_single' => $request->standard_staff_rate_for_single,
                    'standard_staff_rate_for_double' => $request->standard_staff_rate_for_double,
                    'staff_rate_for_seaplane_male' => $request->staff_rate_for_seaplane_male,
                    'male_subsistence_allowance' => $request->male_subsistence_allowance,
                    'status' => $request->status,
                    'custom_fields' => json_encode($customFields)
                ]
            );

            if ($benefitgrid && $benefitgrid->id) {
                 ResortBenifitGridChild::where('benefit_grid_id', $benefitgrid->id)->delete();

                // ✅ Save new child records
                if ($request->has('LeaveCat')) {
                    foreach ($request->LeaveCat as $leaveCatId => $leaveTypes) {
                        foreach ($leaveTypes as $index => $days) {
                            $days = is_array($days) ? $days[0] : $days;

                            $eligibleEmpTypeValue = isset($request->eligible_emp_type[$leaveCatId])
                                ? (is_array($request->eligible_emp_type[$leaveCatId]) 
                                    ? implode(',', $request->eligible_emp_type[$leaveCatId]) 
                                    : $request->eligible_emp_type[$leaveCatId])
                                : null;

                            foreach ($rankArray as $rank) {
                                ResortBenifitGridChild::create([
                                    'benefit_grid_id'     => $benefitgrid->id,
                                    'leave_cat_id'        => $leaveCatId,
                                    'rank'                => $rank,
                                    'allocated_days'      => $days,
                                    'eligible_emp_type'   => $eligibleEmpTypeValue,
                                ]);
                            }
                        }
                    }
                }

                // ✅ Handle custom leave types
                if ($request->has('custom_leave')) {
                    foreach ($request->input('custom_leave') as $customLeave) {
                        $existingLeave = LeaveCategory::where('resort_id', $resort_id)
                            ->where('leave_type', $customLeave['name'])
                            ->first();

                        // eligibility is a rank-id CSV (matches how this
                        // table is seeded and how FinalSettlementService
                        // reads it) — write the resolved ranks, not the raw
                        // grade tag/id, so this stays consistent now that
                        // emp_grade is a resort_benefit_grade_levels id
                        // rather than a small fixed 1-8 key.
                        if ($existingLeave) {
                            $existingEligibility = explode(',', $existingLeave->eligibility);
                            $newRanks = array_diff($rankArray, $existingEligibility);
                            if (!empty($newRanks)) {
                                $existingEligibility = array_merge($existingEligibility, $newRanks);
                                $existingLeave->eligibility = implode(',', $existingEligibility);
                                $existingLeave->save();
                            }
                        } else {
                            LeaveCategory::create([
                                'resort_id' => $resort_id,
                                'leave_type' => $customLeave['name'],
                                'number_of_days' => $customLeave['days'],
                                'carry_forward' => 0,
                                'eligibility' => implode(',', $rankArray),
                                'frequency' => 'Yearly',
                                'number_of_times' => 1,
                                'color' => $this->generateRandomColor(),
                            ]);
                        }
                    }
                }

                // ✅ Handle custom benefits
                if ($request->has('custom_benefit_name')) {
                    foreach ($request->custom_benefit_name as $index => $name) {
                        if (!empty($name)) {
                            CustomBenefit::create([
                                'benefit_grid_id' => $benefitgrid->id,
                                'benefit_name' => $name,
                                'benefit_value' => $request->custom_benefit_value[$index],
                            ]);
                        }
                    }
                }

                // ✅ Handle custom discounts
                if ($request->has('custom_discount_name')) {
                    foreach ($request->custom_discount_name as $index => $name) {
                        if (!empty($name)) {
                            $discountValue = $request->custom_discount_value[$index] ?? null;
                            if (!is_null($discountValue)) {
                                CustomDiscount::create([
                                    'benefit_grid_id' => $benefitgrid->id,
                                    'discount_name' => $name,
                                    'discount_rate' => $discountValue,
                                ]);
                            }
                        }
                    }
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'msg' => __('messages.addSuccess', ['name' => 'Benefit Grid']),
                'redirect_url' => route('resort.benifitgrid.index')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'msg' => $e->getMessage()
            ]);
        }
    }

    public function edit($id)
    {
        if(Common::checkRouteWisePermission('resort.benifitgrid.index',config('settings.resort_permissions.edit')) == false){
            return abort(403, 'Unauthorized access');
        }
        try {
            // Set up the page and form details
            $page_title = 'Edit Benefit Grid';
            $resort_id = Auth::guard('resort-admin')->user()->resort_id;
            $excludedLeaveTypes = ['Absent', 'Present'];
            $LeaveCategories = LeaveCategory::where('resort_id',$resort_id)
            ->whereNotIn('leave_type', $excludedLeaveTypes)->get();
            // Sports options for the form
            $sports = ["Billiard", "Football", "Volleyball", "Fishing trips", "Table tennis", "Beach access", "Karaoke", "Staff gym", "Outdoor cinema", "Fifa & PubG tournaments"];
            $accomodation_type = AccommodationType::where('resort_id', $resort_id)->get();

            // Fetch the existing benefit grid record by ID
            $benefit_grid = ResortBenifitGrid::findOrFail($id);
            $currentGradeName = optional(ResortBenefitGradeLevel::find($benefit_grid->emp_grade))->name ?? '';
            $currentGradeRanks = ResortBenefitGradeLevelRank::where('resort_id', $resort_id)
                ->where('grade_level_id', $benefit_grid->emp_grade)
                ->pluck('rank')
                ->all();
            $rankConfig = config('settings.Position_Rank');
            $benefitGridChildren = ResortBenifitGridChild::where('benefit_grid_id', $id)->get();

            // Create a mapping of leave_cat_id to allocated_days
            $benefitGridChildMap = $benefitGridChildren->keyBy('leave_cat_id');
            // dd($benefitGridChildMap);

            // Split comma-separated fields for multi-selection
            $selected_linen_array = explode(',', $benefit_grid->linen);
            $selected_laundry = explode(',', $benefit_grid->laundry);
            $selected_sports = explode(',', $benefit_grid->sports_and_entertainment_facilities);

            // Indicating that the form is in edit mode
            $isViewMode = false;
            $custom_leave = $benefit_grid->customLeaves;  // Fetch related custom leaves
            $custom_benefits = $benefit_grid->customBenefits;
            $custom_discounts = $benefit_grid->customDiscounts;
            $custom_fields = json_decode($benefit_grid->custom_fields, true) ?? [];
            return view('resorts.benifitgrid.edit', compact(
                'page_title', 'resort_id', 'sports','LeaveCategories','accomodation_type','currentGradeName','currentGradeRanks','rankConfig', 'benefit_grid', 'isViewMode', 'selected_linen_array', 'selected_laundry', 'selected_sports','custom_leave','custom_benefits','custom_discounts','custom_fields','benefitGridChildMap'
            ));
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());

            // Redirect to 404 if something goes wrong
            abort(404);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction(); // ✅ Begin transaction
        try {
            $resort_id = Auth::guard('resort-admin')->user()->resort_id;

            // The grid being edited is identified by $id — everything below
            // used to re-resolve it purely from the submitted Employee
            // Grade name via updateOrCreate(['emp_grade' => ...]), with $id
            // never actually used. If that free-text name normalized to a
            // DIFFERENT existing grade (typo, stale prefill, copy-paste),
            // this silently wrote the submitted changes onto that OTHER
            // grade's grid row instead of the one being edited, leaving
            // grid #$id untouched. Load the real target row up front.
            $benefitgrid = ResortBenifitGrid::where('resort_id', $resort_id)->findOrFail($id);

            // See store() — Employee Grade is a free-text name, resolved
            // to (or created as) the matching grade-level row.
            $gradeLevel = ResortBenefitGradeLevel::firstOrCreate(
                ['resort_id' => $resort_id, 'name' => trim($request->emp_grade)],
                ['status' => 'active']
            );
            $empGrade = (string) $gradeLevel->id;

            // Renaming this grid to a name that resolves to a DIFFERENT
            // grade that already has its own grid would otherwise leave two
            // rows sharing one emp_grade (this row's, plus the other
            // grade's own) — an ambiguous state every emp_grade lookup
            // elsewhere assumes can't happen. Reject instead of merging.
            if ($empGrade !== $benefitgrid->emp_grade) {
                $collision = ResortBenifitGrid::where('resort_id', $resort_id)
                    ->where('emp_grade', $empGrade)
                    ->where('id', '!=', $benefitgrid->id)
                    ->exists();
                if ($collision) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'msg' => 'Another benefit grid already uses the grade name "' . trim($request->emp_grade) . '". Choose a different name.'
                    ]);
                }
            }

            // See store() — ranks submitted on the same form, same shared
            // assignment logic as the standalone config screen.
            $rankArray = array_map('intval', $request->input('ranks', []));
            ResortBenefitGradeLevelRank::assignRanksToGrade($resort_id, $gradeLevel->id, $rankArray);

            // ✅ Handle custom fields
            $customFields = [];
            if ($request->has('custom_field_names') && $request->has('custom_field_values')) {
                foreach ($request->input('custom_field_names') as $index => $name) {
                    if (!empty($name)) {
                        $customFields[] = [
                            'name' => trim($name),
                            'value' => trim($request->input('custom_field_values')[$index]),
                        ];
                    }
                }
            }

            // ✅ Update the grid identified by $id in place (not re-matched
            // by emp_grade — see the collision guard above for why).
            $benefitgrid->update(
                [
                    'resort_id' => $resort_id,
                    'emp_grade' => $empGrade,
                    'rank' => implode(",", $rankArray),
                    'contract_status' => $request->contract_status,
                    'effective_date' => $request->effective_date,
                    'salary_period' => $request->salary_period,
                    'service_charge' => $request->service_charge,
                    'ramadan_bonus' => $request->ramadan_bonus,
                    'ramadan_bonus_eligibility' => $request->ramadan_bonus_eligibility,
                    'uniform' => $request->uniform,
                    'health_care_insurance' => $request->health_care_insurance,
                    'day_off_per_week' => $request->day_off_per_week,
                    'working_hrs_per_week' => $request->working_hrs_per_week, // ✅ corrected
                    'public_holiday_per_year' => $request->public_holiday_per_year,
                    'paid_worked_public_holiday_and_friday' => $request->paid_worked_public_holiday_and_friday,
                    'relocation_ticket' => $request->relocation_ticket,
                    'max_excess_luggage_relocation_expense' => $request->max_excess_luggage_relocation_expense,
                    'ticket_upon_termination' => $request->ticket_upon_termination,
                    'meals_per_day' => $request->meals_per_day,
                    'accommodation_status' => $request->accommodation_status,
                    'furniture_and_fixtures' => $request->furniture_and_fixtures,
                    'housekeeping' => $request->housekeeping,
                    'linen' => !empty($request->linen) ? implode(",", $request->linen) : "",
                    'laundry' => !empty($request->laundry) ? implode(",", $request->laundry) : "",
                    'internet_access' => $request->internet_access,
                    'telephone' => $request->telephone,
                    'annual_leave_ticket' => $request->annual_leave_ticket,
                    'overtime' => $request->overtime,
                    'salary_paid_in' => $request->salary_paid_in,
                    'loan_and_salary_advanced' => $request->loan_and_salary_advanced,
                    'sports_and_entertainment_facilities' => !empty($request->sports_and_entertainment_facilities) ? implode(",", $request->sports_and_entertainment_facilities) : "",
                    'free_return_flight_to_male_per_year' => $request->free_return_flight_to_male_per_year,
                    'food_and_beverages_discount' => $request->food_and_beverages_discount,
                    'alchoholic_beverages_discount' => $request->alchoholic_beverages_discount,
                    'spa_discount' => $request->spa_discount,
                    'dive_center_discount' => $request->dive_center_discount,
                    'water_sports_discount' => $request->water_sports_discount,
                    'friends_with_benefit_discount' => $request->friends_with_benefit_discount,
                    'standard_staff_rate_for_single' => $request->standard_staff_rate_for_single,
                    'standard_staff_rate_for_double' => $request->standard_staff_rate_for_double,
                    'staff_rate_for_seaplane_male' => $request->staff_rate_for_seaplane_male,
                    'male_subsistence_allowance' => $request->male_subsistence_allowance,
                    'status' => $request->status,
                    'custom_fields' => json_encode($customFields)
                ]
            );

            // ✅ Update benefit grid child records
            if ($benefitgrid->id && $request->has('LeaveCat')) {
                ResortBenifitGridChild::where('benefit_grid_id', $benefitgrid->id)->delete();

                foreach ($request->LeaveCat as $k => $l_type) {
                    foreach ($l_type as $ak => $days) {
                        $days = is_array($days) ? $days[0] : $days;

                        $eligible_emp_type_value = isset($request->eligible_emp_type[$k])
                            ? (is_array($request->eligible_emp_type[$k]) ? implode(',', $request->eligible_emp_type[$k]) : $request->eligible_emp_type[$k])
                            : null;

                        foreach ($rankArray as $newrank) {
                            ResortBenifitGridChild::create([
                                'benefit_grid_id' => $benefitgrid->id,
                                'leave_cat_id' => $k,
                                'allocated_days' => $days,
                                'rank' => $newrank,
                                'eligible_emp_type' => $eligible_emp_type_value,
                            ]);
                        }
                    }
                }
            }

            // ✅ Handle custom leave types
            if ($request->has('custom_leave')) {
                foreach ($request->input('custom_leave') as $customLeave) {
                    $existingLeave = LeaveCategory::where('resort_id', $resort_id)
                        ->where('leave_type', $customLeave['name'])
                        ->first();

                    // See store() — eligibility is a rank-id CSV, write
                    // the resolved ranks, not the raw grade tag/id.
                    if ($existingLeave) {
                        $existingEligibility = explode(',', $existingLeave->eligibility);
                        $newRanks = array_diff($rankArray, $existingEligibility);
                        if (!empty($newRanks)) {
                            $existingEligibility = array_merge($existingEligibility, $newRanks);
                            $existingLeave->eligibility = implode(',', $existingEligibility);
                            $existingLeave->save();
                        }
                    } else {
                        LeaveCategory::create([
                            'resort_id' => $resort_id,
                            'leave_type' => $customLeave['name'],
                            'number_of_days' => $customLeave['days'],
                            'carry_forward' => 0,
                            'eligibility' => implode(',', $rankArray),
                            'frequency' => 'Yearly',
                            'number_of_times' => 1,
                            'color' => $this->generateRandomColor(),
                        ]);
                    }
                }
            }

            // ✅ Handle custom benefits
            if ($request->has('custom_benefit_name')) {
                foreach ($request->custom_benefit_name as $index => $name) {
                    if (!empty($name)) {
                        CustomBenefit::create([
                            'benefit_grid_id' => $benefitgrid->id,
                            'benefit_name' => $name,
                            'benefit_value' => $request->custom_benefit_value[$index],
                        ]);
                    }
                }
            }

            // ✅ Handle custom discounts
            if ($request->has('custom_discount_name')) {
                foreach ($request->custom_discount_name as $index => $name) {
                    if (!empty($name)) {
                        $discountValue = $request->custom_discount_value[$index] ?? null;
                        if (!is_null($discountValue)) {
                            CustomDiscount::create([
                                'benefit_grid_id' => $benefitgrid->id,
                                'discount_name' => $name,
                                'discount_rate' => $discountValue,
                            ]);
                        }
                    }
                }
            }

            DB::commit(); // ✅ Commit transaction

            return response()->json([
                'success' => true,
                'msg' => __('messages.updateSuccess', ['name' => 'Benefit Grid']),
                'redirect_url' => route('resort.benifitgrid.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // ❗️ Roll back on error

            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'msg' => $e->getMessage()
            ]);
        }
    }

    public function view($id)
    {
        if(Common::checkRouteWisePermission('resort.benifitgrid.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
        
        try {
            $page_title = 'View Benefit Grid';
            $resort_id = Auth::guard('resort-admin')->user()->resort_id;
            $sports = ["Billiard", "Football", "Volleyball", "Fishing trips", "Table tennis", "Beach access", "Karaoke", "Staff gym", "Outdoor cinema", "Fifa & PubG tournaments"];
            $benefit_grid = ResortBenifitGrid::where('id', $id)
                    ->where('resort_id', $resort_id)
                    ->first();

           
            $benefitGridChildren = ResortBenifitGridChild::join('leave_categories as lc', 'lc.id', '=', 'resort_benefit_grid_child.leave_cat_id')
            ->where('benefit_grid_id', $benefit_grid->id)
            // Child rows are already uniquely scoped by benefit_grid_id —
            // the extra rank filter was redundant at best and actively
            // broken for grouped grades (e.g. EXCOM's ranks [1,3,7,8] only
            // ever coincidentally matched the rank=1 subset via the old
            // emp_grade key), hiding rows for grades spanning >1 rank.
            ->select('resort_benefit_grid_child.*', 'lc.leave_type as leave_category_name') // optional
            ->get();

            $isViewMode = true;  // Set flag to indicate this is editing an existing record
            $selected_linen_array = explode(',', $benefit_grid->linen);
            $selected_laundry = explode(',', $benefit_grid->laundry);
            $selected_sports =  explode(',', $benefit_grid->sports_and_entertainment_facilities);
            $LeaveCategories = LeaveCategory::where('resort_id',$resort_id)->get();

            return view('resorts.benifitgrid.view')->with(compact(
                'page_title', 'resort_id', 'sports', 'benefit_grid','benefitGridChildren', 'isViewMode','selected_linen_array','selected_laundry','selected_sports','LeaveCategories'
            ));
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());

            // Redirect to a 404 error page if something goes wrong
            abort(404);
        }
    }

    public function pdf($id){
        try{ 
            $benefit_grid = ResortBenifitGrid::findOrFail($id);
            $sitesettings = ResortSiteSettings::where('resort_id', $benefit_grid->resort_id)->first(['resort_id','header_img','footer_img','Footer']);
            $ResortData = Resort::find($benefit_grid->resort_id);
            $resort_id = $ResortData->resort_id;
           $benefitGridChildren = ResortBenifitGridChild::join('leave_categories as lc', 'lc.id', '=', 'resort_benefit_grid_child.leave_cat_id')
            ->where('benefit_grid_id', $benefit_grid->id)
            // Child rows are already uniquely scoped by benefit_grid_id —
            // the extra rank filter was redundant at best and actively
            // broken for grouped grades (e.g. EXCOM's ranks [1,3,7,8] only
            // ever coincidentally matched the rank=1 subset via the old
            // emp_grade key), hiding rows for grades spanning >1 rank.
            ->select('resort_benefit_grid_child.*', 'lc.leave_type as leave_category_name') // optional
            ->get();
            // dd($benefit_grid);
            $html = view('resorts.benifitgrid.pdf', [
            $resort_id ,'benefit_grid' => $benefit_grid,'sitesettings'=>$sitesettings,'benefitGridChildren'=>$benefitGridChildren,'resort_id'=>$resort_id,'ResortData'=>$ResortData])->render();

            return $html;
        }
        catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());

            // Redirect to a 404 error page if something goes wrong
            abort(404);
        }
    }

    public function generateRandomColor() {
        return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
    }

    public function viewByLevel($id)
    {
        try {
            $page_title = 'Benefit Grid';
            $resort_id = Auth::guard('resort-admin')->user()->resort_id;
            $sports = ["Billiard", "Football", "Volleyball", "Fishing trips", "Table tennis", "Beach access", "Karaoke", "Staff gym", "Outdoor cinema", "Fifa & PubG tournaments"];
            $benefit_grid = ResortBenifitGrid::where('emp_grade', $id)
                    ->where('resort_id', $resort_id)
                    ->first();

            if (!$benefit_grid) {
                abort(404);
            }

            // Mirror view($id): scope children to THIS benefit grid and pull
            // the leave_category_name from leave_categories — otherwise the
            // page shows "N/A (In Days)" for every row. Filtering by
            // benefit_grid_id alone is correct (see view()'s comment) —
            // the old extra rank filter hid rows for grades spanning >1 rank.
            $benefitGridChildren = ResortBenifitGridChild::join('leave_categories as lc', 'lc.id', '=', 'resort_benefit_grid_child.leave_cat_id')
                ->where('benefit_grid_id', $benefit_grid->id)
                ->select('resort_benefit_grid_child.*', 'lc.leave_type as leave_category_name')
                ->get();

            $isViewMode = true;
            $selected_linen_array = explode(',', $benefit_grid->linen);
            $selected_laundry = explode(',', $benefit_grid->laundry);
            $selected_sports =  explode(',', $benefit_grid->sports_and_entertainment_facilities);
            $LeaveCategories = LeaveCategory::where('resort_id', $resort_id)->get();

            return view('resorts.benifitgrid.view')->with(compact(
                'page_title', 'resort_id', 'sports', 'benefit_grid', 'benefitGridChildren',
                'isViewMode', 'selected_linen_array', 'selected_laundry', 'selected_sports',
                'LeaveCategories'
            ));
        } catch (\Exception $e) {
            \Log::emergency("File: " . $e->getFile());
            \Log::emergency("Line: " . $e->getLine());
            \Log::emergency("Message: " . $e->getMessage());

            // Redirect to a 404 error page if something goes wrong
            abort(404);
        }
    }

}
?>
