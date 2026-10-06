<?php

namespace Tests\Feature;

use App\Models\ResortAdmin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * A new resort's master admin (not an employee): employee-only pages send it
 * to the Permission page instead of erroring, and the HR department's EXCOM
 * position gets full page access by default.
 */
class ResortSetupAccessTest extends TestCase
{
    use DatabaseTransactions;

    private int $resortId;
    private array $modules;
    private int $pageCount;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->resortId = DB::table('resorts')->insertGetId(['resort_name' => 'Access Test', 'resort_email' => 'access@resort.test', 'status' => 'active',
            'resort_id' => 'acctest001', 'resort_prefix' => 'ACCT', 'resort_it_email' => 'it@access.test', 'created_at' => now(), 'updated_at' => now()]);
        // The resort has two modules.
        $this->modules = DB::table('module_pages')->where('status', 'Active')->whereNull('deleted_at')->distinct()->limit(2)->pluck('Module_Id')->all();
        foreach (DB::table('module_pages')->whereIn('Module_Id', $this->modules)->pluck('id', 'Module_Id') as $module => $page) {
            DB::table('resort_pagewise_permissions')->insert(['resort_id' => $this->resortId, 'Module_id' => $module, 'page_permission_id' => $page]);
        }
        $this->pageCount = DB::table('module_pages')->whereIn('Module_Id', $this->modules)->where('status', 'Active')->whereNull('deleted_at')->count();

        $admin = new ResortAdmin;
        $admin->forceFill(['resort_id' => $this->resortId, 'first_name' => 'Access', 'last_name' => 'Admin', 'email' => 'access.admin@resort.test',
            'gender' => 'male', 'status' => 'active', 'type' => 'super', 'role_id' => 0, 'is_master_admin' => 1, 'is_employee' => 0,
            'password' => Hash::make('x')])->save();
        $this->actingAs($admin, 'resort-admin');
    }

    public function test_master_admin_is_sent_to_permission_page_instead_of_an_error()
    {
        // BudgetController reads GetEmployee->Dept_id, which is null for a master admin.
        $this->get(route('resort.budget.manning'))
            ->assertRedirect(route('resort.Page.Permission', ['notice' => 'employee-only']));
        $this->getJson(route('resort.budget.manning'))->assertStatus(403)->assertJson(['success' => false]);

        $this->get(route('resort.Page.Permission', ['notice' => 'employee-only']))->assertOk()
            ->assertSee('That page works for employees only.')
            ->assertSee('No divisions yet.')
            ->assertSee(route('resort.manning.index'), false);
    }

    public function test_structure_can_be_created_without_typing_a_code()
    {
        // The exact request the Resort Configuration form sends with Code left blank.
        $this->postJson(route('manning.divisions.store'), ['division_id' => 'Administrative and General', 'code' => '', 'status' => 'active'])
            ->assertOk()->assertJson(['success' => true]);
        $division = DB::table('resort_divisions')->where('resort_id', $this->resortId)->where('name', 'Administrative and General')->first();
        $this->assertMatchesRegularExpression('/^ADMIN_\d+$/', $division->code);

        $this->postJson(route('manning.departments.store'), ['division_id' => $division->id, 'name' => 'Human Resources', 'code' => '', 'status' => 'active'])
            ->assertOk();
        $dept = DB::table('resort_departments')->where('resort_id', $this->resortId)->where('name', 'Human Resources')->first();
        $this->assertMatchesRegularExpression('/^HUMAN_\d+$/', $dept->code);

        $this->postJson(route('manning.sections.store'), ['dept_id' => $dept->id, 'name' => 'Recruitment', 'code' => '', 'status' => 'active'])
            ->assertOk();
        $this->assertMatchesRegularExpression('/^RECRU_\d+$/', DB::table('resort_sections')->where('resort_id', $this->resortId)->where('name', 'Recruitment')->value('code'));

        // Editing with Code still blank keeps the code it has.
        $this->putJson(route('manning.divisions.inlineUpdate', $division->id), ['name' => 'Admin & General', 'code' => '', 'status' => 'active'])->assertOk();
        $this->assertSame($division->code, DB::table('resort_divisions')->where('id', $division->id)->value('code'));
    }

    public function test_hr_head_position_gets_full_access_by_default()
    {
        $division = DB::table('resort_divisions')->insertGetId(['resort_id' => $this->resortId, 'name' => 'Admin', 'code' => 'ADM', 'short_name' => 'ADM']);
        $dept = fn ($name) => DB::table('resort_departments')->insertGetId(['resort_id' => $this->resortId, 'division_id' => $division,
            'name' => $name, 'code' => substr($name, 0, 3), 'short_name' => substr($name, 0, 3)]);
        $hr = $dept('HUMAN RESOURCES');
        $finance = $dept('FINANCE');
        $perms = fn ($positionId) => DB::table('resort_interal_pages_permissions')->where('resort_id', $this->resortId)->where('position_id', $positionId);
        $store = fn ($deptId, $title, $rank) => $this->postJson(route('manning.positions.store'), ['dept_id' => $deptId, 'position_title' => $title,
            'status' => 'active', 'Rank' => $rank, 'no_of_positions' => 1]);
        $id = fn ($title) => DB::table('resort_positions')->where('resort_id', $this->resortId)->where('position_title', $title)->value('id');

        // HR + EXCOM → every page of the resort's modules, view/create/edit/delete.
        $store($hr, 'Director Of HR', 1)->assertOk();
        $head = $id('Director Of HR');
        $this->assertSame($this->pageCount * 4, $perms($head)->count());
        $this->assertSame([$hr], $perms($head)->distinct()->pluck('Dept_id')->map(fn ($v) => (int) $v)->all());
        $this->assertSame([1, 2, 3, 4], $perms($head)->distinct()->orderBy('Permission_id')->pluck('Permission_id')->map(fn ($v) => (int) $v)->all());

        // Not the HR head → nothing by default.
        $store($finance, 'Director Of Finance', 1)->assertOk();
        $store($hr, 'HR Manager', 4)->assertOk();
        $this->assertSame(0, $perms($id('Director Of Finance'))->count());
        $this->assertSame(0, $perms($id('HR Manager'))->count());

        // Promoted to EXCOM inline → gets it; a position with access already set keeps it.
        $inline = fn ($positionId, $title) => $this->putJson(route('manning.positions.inlineUpdate', $positionId), [
            'department' => $hr, 'name' => $title, 'status' => 'active', 'Rank' => 1, 'no_of_positions' => 1]);
        $inline($id('HR Manager'), 'HR Manager')->assertOk()->assertJson(['success' => true, 'divisionName' => 'Admin', 'deptName' => 'HUMAN RESOURCES']);
        $this->assertSame($this->pageCount * 4, $perms($id('HR Manager'))->count());

        $store($hr, 'HR Coordinator', 6)->assertOk();
        $coordinator = $id('HR Coordinator');
        DB::table('resort_interal_pages_permissions')->insert(['resort_id' => $this->resortId, 'Dept_id' => $hr, 'position_id' => $coordinator,
            'page_id' => DB::table('module_pages')->whereIn('Module_Id', $this->modules)->value('id'), 'Permission_id' => 1]);
        $inline($coordinator, 'HR Coordinator')->assertOk();
        $this->assertSame(1, $perms($coordinator)->count(), 'access the resort set itself is never overwritten');
    }
}
