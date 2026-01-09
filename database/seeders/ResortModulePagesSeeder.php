<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ModulePages;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResortModulePagesSeeder extends Seeder
{
    public function run(): void
    {
        // Optional: clear table before seeding
        DB::table('module_pages')->truncate();

        $now = Carbon::now();

        // Main data (without auto fields)
        $pages = [
           [
              "page_name"       => "Support List",
              "Module_Id"       => "13",
              "internal_route"  => "support.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "1"
            ],
           [
              "page_name"       => "Manning",
              "Module_Id"       => "1",
              "internal_route"  => "resort.budget.manning",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "2"
            ],
           [
              "page_name"       => "Budget",
              "Module_Id"       => "1",
              "internal_route"  => "resort.budget.viewbudget",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "3"
            ],
           [
              "page_name"       => "Consolidate Budget",
              "Module_Id"       => "1",
              "internal_route"  => "resort.budget.consolidatebudget",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "4"
            ],
           [
              "page_name"       => "Configuration",
              "Module_Id"       => "1",
              "internal_route"  => "resort.budget.config",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "5"
            ],
           [
              "page_name"       => "Cost Config Page",
              "Module_Id"       => "1",
              "internal_route"  => "resort.budget.index",
              "TypeOfPage"      => "InsideOfPage",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Benefit Grid",
              "Module_Id"       => "1",
              "internal_route"  => "resort.benifitgrid.index",
              "TypeOfPage"      => "InsideOfPage",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Settings",
              "Module_Id"       => "22",
              "internal_route"  => "resort.sitesettings",
              "TypeOfPage"      => "InsideOfPage",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Dashboard",
              "Module_Id"       => "3",
              "internal_route"  => "resort.recruitement.hrdashboard",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "1"
            ],
           [
              "page_name"       => "Configuration",
              "Module_Id"       => "3",
              "internal_route"  => "resort.ta.configration",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "7"
            ],
           [
              "page_name"       => "Talent Pool",
              "Module_Id"       => "3",
              "internal_route"  => "resort.ta.TalentPool",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "2"
            ],
           [
              "page_name"       => "Shortlisted Applicants",
              "Module_Id"       => "3",
              "internal_route"  => "resort.ta.shortlistedapplicants",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "3"
            ],
           [
              "page_name"       => "Moving to talent pool",
              "Module_Id"       => "3",
              "internal_route"  => "resort.ta.getTalentPoolApplicant",
              "TypeOfPage"      => "InsideOfPage",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Dashboard",
              "Module_Id"       => "1",
              "internal_route"  => "resort.workforceplan.dashboard",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "1"
            ],
           [
              "page_name"       => "Compare Budget",
              "Module_Id"       => "1",
              "internal_route"  => "resort.budget.comparebudget,{id]",
              "TypeOfPage"      => "InsideOfPage",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Dashboard",
              "Module_Id"       => "9",
              "internal_route"  => "learning.hr.dashboard",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "1"
            ],
           [
              "page_name"       => "Create Duty Roster",
              "Module_Id"       => "5",
              "internal_route"  => "resort.timeandattendance.CreateDutyRoster",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "2"
            ],
           [
              "page_name"       => "Questionnaire",
              "Module_Id"       => "3",
              "internal_route"  => "resort.ta.Questionnaire",
              "TypeOfPage"      => "InsideOfPage",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Job Description",
              "Module_Id"       => "3",
              "internal_route"  => "resort.ta.jobdescription.index",
              "TypeOfPage"      => "InsideOfPage",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Interview Assessment",
              "Module_Id"       => "3",
              "internal_route"  => "interview-assessment.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "6"
            ],
           [
              "page_name"       => "Email Templates",
              "Module_Id"       => "3",
              "internal_route"  => "resort.ta.emailtemplates",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "4"
            ],
           [
              "page_name"       => "Vacancies",
              "Module_Id"       => "3",
              "internal_route"  => "resort.vacancies.FreshApplicant",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "5"
            ],
           [
              "page_name"       => "Dashboard",
              "Module_Id"       => "5",
              "internal_route"  => "resort.timeandattendance.dashboard",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "1"
            ],
           [
              "page_name"       => "Employee",
              "Module_Id"       => "5",
              "internal_route"  => "resort.timeandattendance.employee",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "5"
            ],
           [
              "page_name"       => "Attandance Register",
              "Module_Id"       => "5",
              "internal_route"  => "resort.timeandattendance.AttandanceRegister",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "4"
            ],
           [
              "page_name"       => "Location History",
              "Module_Id"       => "5",
              "internal_route"  => "resort.timeandattendance.LocationHistory",
              "TypeOfPage"      => "InsideOfPage",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Overtime",
              "Module_Id"       => "5",
              "internal_route"  => "resort.timeandattendance.OverTime",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "6"
            ],
           [
              "page_name"       => "Configuration",
              "Module_Id"       => "5",
              "internal_route"  => "resort.timeandattendance.Configration",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "7"
            ],
           [
              "page_name"       => "Todo List",
              "Module_Id"       => "5",
              "internal_route"  => "resort.timeandattendance.todolist",
              "TypeOfPage"      => "InsideOfPage",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Dashboard",
              "Module_Id"       => "10",
              "internal_route"  => "resort.accommodation.dashboard",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Maintenance Request",
              "Module_Id"       => "10",
              "internal_route"  => "resort.accommodation.MaintanaceRequestlist",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Configuration",
              "Module_Id"       => "10",
              "internal_route"  => "resort.accommodation.config.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Inventory",
              "Module_Id"       => "10",
              "internal_route"  => "resort.accommodation.inventory",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Inventory Management",
              "Module_Id"       => "10",
              "internal_route"  => "resort.accommodation.InventoryManagement",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Assign Accommodation",
              "Module_Id"       => "10",
              "internal_route"  => "resort.accommodation.AssignAccommation",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Accommodation Master",
              "Module_Id"       => "10",
              "internal_route"  => "resort.accommodation.AccommodationMaster",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Available Accommodation",
              "Module_Id"       => "10",
              "internal_route"  => "resort.accommodation.AvailableAccommodation",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Employee Accommodation",
              "Module_Id"       => "10",
              "internal_route"  => "resort.accommodation.EmployeeAccommodation",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Hold Maintanace Request",
              "Module_Id"       => "10",
              "internal_route"  => "resort.accommodation.HoldMaintanaceRequest",
              "TypeOfPage"      => "InsideOfPage",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Event",
              "Module_Id"       => "10",
              "internal_route"  => "resort.accommodation.event",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Dashboard",
              "Module_Id"       => "7",
              "internal_route"  => "Performance.Hrdashboard",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Meeting",
              "Module_Id"       => "7",
              "internal_route"  => "Performance.Meeting.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Cycle",
              "Module_Id"       => "7",
              "internal_route"  => "Performance.cycle",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Configuration",
              "Module_Id"       => "7",
              "internal_route"  => "Performance.configuration",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Dashboard",
              "Module_Id"       => "6",
              "internal_route"  => "leave.dashboard",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Leave Apply",
              "Module_Id"       => "6",
              "internal_route"  => "leave.apply",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Leave Request",
              "Module_Id"       => "6",
              "internal_route"  => "leave.request",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Calendar",
              "Module_Id"       => "6",
              "internal_route"  => "leave.calendar",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Up Coming Holiday",
              "Module_Id"       => "6",
              "internal_route"  => "resort.upcomingholiday.list",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Configuration",
              "Module_Id"       => "6",
              "internal_route"  => "leave.configration",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Dashboard",
              "Module_Id"       => "2",
              "internal_route"  => "payroll.dashboard",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "1"
            ],
           [
              "page_name"       => "Shopkeepers",
              "Module_Id"       => "2",
              "internal_route"  => "shopkeepers.create",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "2"
            ],
           [
              "page_name"       => "Run Pay Roll",
              "Module_Id"       => "2",
              "internal_route"  => "payroll.run",
              "TypeOfPage"      => "InsideOfPage",
              "type"            => "normal",
              "place_order"     => "9"
            ],
           [
              "page_name"       => "Pension",
              "Module_Id"       => "2",
              "internal_route"  => "payroll.pension.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "3"
            ],
           [
              "page_name"       => "EWT",
              "Module_Id"       => "2",
              "internal_route"  => "payroll.ewt.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "4"
            ],
           [
              "page_name"       => "Final Settlement",
              "Module_Id"       => "2",
              "internal_route"  => "payroll.final.settlement",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "5"
            ],
           [
              "page_name"       => "Configuration",
              "Module_Id"       => "2",
              "internal_route"  => "payroll.configration",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "8"
            ],
           [
              "page_name"       => "Programs",
              "Module_Id"       => "9",
              "internal_route"  => "learning.programs.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "2"
            ],
           [
              "page_name"       => "Add Request",
              "Module_Id"       => "9",
              "internal_route"  => "learning.request.add",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "3"
            ],
           [
              "page_name"       => "Calendar",
              "Module_Id"       => "9",
              "internal_route"  => "learning.calendar.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "4"
            ],
           [
              "page_name"       => "Configuration",
              "Module_Id"       => "9",
              "internal_route"  => "learning.configration",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Configuration",
              "Module_Id"       => "12",
              "internal_route"  => "GrievanceAndDisciplinery.config.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Dashboard",
              "Module_Id"       => "12",
              "internal_route"  => "GrievanceAndDisciplinery.Hrdashboard",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Grievance",
              "Module_Id"       => "12",
              "internal_route"  => "GrievanceAndDisciplinery.grivance.GrivanceIndex",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Dashboard",
              "Module_Id"       => "14",
              "internal_route"  => "Survey.hr.dashboard",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Survey",
              "Module_Id"       => "14",
              "internal_route"  => "Survey.Surveylist",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Create Maintenance Request",
              "Module_Id"       => "10",
              "internal_route"  => "resort.accommodation.CreateMaintenanceRequest",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Disciplinary List",
              "Module_Id"       => "12",
              "internal_route"  => "GrievanceAndDisciplinery.Disciplinary.DisciplinaryIndex",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Schedule Learning",
              "Module_Id"       => "9",
              "internal_route"  => "learning.schedule",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "5"
            ],
           [
              "page_name"       => "Dashboard",
              "Module_Id"       => "12",
              "internal_route"  => "incident.hr.dashboard",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "1"
            ],
           [
              "page_name"       => "Configuration",
              "Module_Id"       => "12",
              "internal_route"  => "incident.configration",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "5"
            ],
           [
              "page_name"       => "Incident List",
              "Module_Id"       => "12",
              "internal_route"  => "incident.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "2"
            ],
           [
              "page_name"       => "Incident Meeting",
              "Module_Id"       => "12",
              "internal_route"  => "incident.meeting",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "3"
            ],
           [
              "page_name"       => "Calendar",
              "Module_Id"       => "12",
              "internal_route"  => "incident.calendar",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "4"
            ],
           [
              "page_name"       => "Permission",
              "Module_Id"       => "16",
              "internal_route"  => "FileManage.Permission",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "3"
            ],
           [
              "page_name"       => "Employees Documents",
              "Module_Id"       => "16",
              "internal_route"  => "Employees.Documents",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "1"
            ],
           [
              "page_name"       => "Uncategorized Documents",
              "Module_Id"       => "16",
              "internal_route"  => "Categories.Documents",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "1"
            ],
           [
              "page_name"       => "Dashboard",
              "Module_Id"       => "16",
              "internal_route"  => "FileManagment.hr.dashboard",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Monlty Check In",
              "Module_Id"       => "7",
              "internal_route"  => "Performance.MonltyCheckIn",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "5"
            ],
           [
              "page_name"       => "Configuration",
              "Module_Id"       => "17",
              "internal_route"  => "sos.config.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "1"
            ],
           [
              "page_name"       => "Boarding Pass",
              "Module_Id"       => "6",
              "internal_route"  => "resort.boardingpass.list",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "5"
            ],
           [
              "page_name"       => "Info Update",
              "Module_Id"       => "4",
              "internal_route"  => "people.info-update.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "5"
            ],
           [
              "page_name"       => "Dashboard",
              "Module_Id"       => "17",
              "internal_route"  => "sos.dashboard.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Dashboard",
              "Module_Id"       => "4",
              "internal_route"  => "people.hr.dashboard",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "1"
            ],
           [
              "page_name"       => "Configuration",
              "Module_Id"       => "4",
              "internal_route"  => "people.config",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "6"
            ],
           [
              "page_name"       => "Promotion Dashboard",
              "Module_Id"       => "4",
              "internal_route"  => "people.promotion.dashboard",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "2"
            ],
           [
              "page_name"       => "Probation",
              "Module_Id"       => "4",
              "internal_route"  => "people.probation",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "3"
            ],
           [
              "page_name"       => "Initiate Transfer",
              "Module_Id"       => "4",
              "internal_route"  => "people.transfer.initiate",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "4"
            ],
           [
              "page_name"       => "Onboarding Configuration",
              "Module_Id"       => "4",
              "internal_route"  => "people.onboarding.config",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "8"
            ],
           [
              "page_name"       => "Onboarding Creation",
              "Module_Id"       => "4",
              "internal_route"  => "people.onboarding.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "7"
            ],
           [
              "page_name"       => "Report List",
              "Module_Id"       => "12",
              "internal_route"  => "resort.report.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Salary Increment Managment",
              "Module_Id"       => "4",
              "internal_route"  => "people.salary-increment.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "9"
            ],
           [
              "page_name"       => "Salary Increment Summary",
              "Module_Id"       => "4",
              "internal_route"  => "people.salary-increment.summary-list",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "10"
            ],
           [
              "page_name"       => "Salary Advance",
              "Module_Id"       => "4",
              "internal_route"  => "people.advance-salary.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "11"
            ],
           [
              "page_name"       => "Loan Salary Advance Repayment Tracker",
              "Module_Id"       => "4",
              "internal_route"  => "people.advance-salary-repayment-tracker.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "11"
            ],
           [
              "page_name"       => "Exit Clearance",
              "Module_Id"       => "4",
              "internal_route"  => "people.exit-clearance",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "12"
            ],
           [
              "page_name"       => "Itiernaries List",
              "Module_Id"       => "4",
              "internal_route"  => "people.onboarding.itinerary.list",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "14"
            ],
           [
              "page_name"       => "Employee Resignation",
              "Module_Id"       => "4",
              "internal_route"  => "people.employee-resignation.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "12"
            ],
           [
              "page_name"       => "Initial Liability Estimation",
              "Module_Id"       => "4",
              "internal_route"  => "people.liability.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "16"
            ],
           [
              "page_name"       => "Deposit Request",
              "Module_Id"       => "14",
              "internal_route"  => "resort.visa.DepositRequest",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "1"
            ],
           [
              "page_name"       => "Payment Request List",
              "Module_Id"       => "14",
              "internal_route"  => "resort.visa.PaymentRequestIndex",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "3"
            ],
           [
              "page_name"       => "Create Payment Request",
              "Module_Id"       => "14",
              "internal_route"  => "resort.visa.PaymentRequest",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "2"
            ],
           [
              "page_name"       => "Verify Details",
              "Module_Id"       => "14",
              "internal_route"  => "resort.visa.VerifyDetails",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "4"
            ],
           [
              "page_name"       => "Expiry",
              "Module_Id"       => "14",
              "internal_route"  => "resort.visa.Expiry",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "6"
            ],
           [
              "page_name"       => "Xpact Sync",
              "Module_Id"       => "14",
              "internal_route"  => "resort.visa.XpactSync",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "1"
            ],
           [
              "page_name"       => "Xpact Employee",
              "Module_Id"       => "14",
              "internal_route"  => "resort.visa.xpactEmployee",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "5"
            ],
           [
              "page_name"       => "Renewal",
              "Module_Id"       => "14",
              "internal_route"  => "resort.visa.RenewalView",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "7"
            ],
           [
              "page_name"       => "Dashboard",
              "Module_Id"       => "14",
              "internal_route"  => "visa.hr.dashboard",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "1"
            ],
           [
              "page_name"       => "Configuration",
              "Module_Id"       => "14",
              "internal_route"  => "visa.config",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "9"
            ],
           [
              "page_name"       => "Liabilities",
              "Module_Id"       => "14",
              "internal_route"  => "resort.visa.Liabilities",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "8"
            ],
           [
              "page_name"       => "Compliances",
              "Module_Id"       => "13",
              "internal_route"  => "people.compliance.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "1"
            ],
           [
              "page_name"       => "Learning List",
              "Module_Id"       => "9",
              "internal_route"  => "learning.schedule.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "6"
            ],
           [
              "page_name"       => "Learning Request List",
              "Module_Id"       => "9",
              "internal_route"  => "learning.request.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "7"
            ],
           [
              "page_name"       => "Request Detail",
              "Module_Id"       => "9",
              "internal_route"  => "learning.request.details",
              "TypeOfPage"      => "InsideOfPage",
              "type"            => "para",
              "place_order"     => "7"
            ],
           [
              "page_name"       => "Setting",
              "Module_Id"       => "14",
              "internal_route"  => "resort.sitesettings",
              "TypeOfPage"      => "InsideOfPage",
              "type"            => "normal",
              "place_order"     => "0"
            ],
           [
              "page_name"       => "Final Settlement List",
              "Module_Id"       => "2",
              "internal_route"  => "final.settlement.list",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "6"
            ],
           [
              "page_name"       => "Organization chart",
              "Module_Id"       => "4",
              "internal_route"  => "people.org-chart",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "12"
            ],
           [
              "page_name"       => "Approval Request",
              "Module_Id"       => "4",
              "internal_route"  => "people.approvel.index",
              "TypeOfPage"      => "InsideOfMenu",
              "type"            => "normal",
              "place_order"     => "17"
           ],
            [
                "page_name"       => "View Duty Roster",
                "Module_Id"       => "5",
                "internal_route"  => "resort.timeandattendance.ViewDutyRoster",
                "TypeOfPage"      => "InsideOfMenu",
                "type"            => "normal",
                "place_order"     => "3"
              ]
        ];


        foreach ($pages as $page) {
            ModulePages::updateOrCreate(
                ['internal_route' => $page['internal_route']], // ← unique key
                array_merge($page, [
                    'status' => 'Active',
                    'created_by' => 1,
                    'modified_by' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ])
            );
        }

        $this->command->info('✅ Resort module pages seeded successfully.');

    }
}
