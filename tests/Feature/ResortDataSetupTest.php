<?php

namespace Tests\Feature;

use App\Helpers\StorageHelper;
use App\Jobs\RunResortDataImport;
use App\Models\Admin;
use App\Models\ResortDataImport;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * End to end over the real client export files in demo/: upload → AI layout
 * detection (faked) → cache reuse → manual mapping → options → dry run →
 * reauth gate → import → re-import, checked against the client sheet's own
 * totals columns, then a login with an imported account.
 */
class ResortDataSetupTest extends TestCase
{
    use DatabaseTransactions;

    // What a model answers for each demo file: [type, header_row, columns, group_column].
    const AI = [
        'fusion division.xls' => ['divisions', 11, ['name' => 1, 'short_name' => 4, 'status' => 7], null],
        'department.xls'      => ['departments', 7, ['name' => 1, 'short_name' => 4, 'status' => 6, 'division' => 9], null],
        'section.xls'         => ['sections', 10, ['name' => 1, 'short_name' => 5, 'division_department' => 6, 'status' => 9], null],
        'positions.xls'       => ['positions', 8, ['division' => 1, 'department' => 2, 'section' => 5, 'title' => 7], null],
        'level.xls'           => ['levels', 9, ['name' => 1], null],
        'staff list.xls'      => ['staff', 10, ['emp_id' => 1, 'name' => 2, 'hire_date' => 5, 'position' => 6, 'level' => 7, 'section' => 8,
                                                'gender' => 11, 'nationality' => 12, 'religion' => 13], 2],
        'June Attendance.xls' => ['attendance', 1, ['emp_id' => 1, 'name' => 2], 2],
    ];

    private int $resortId;
    private int $shiftId;
    private array $category = [];

    protected function setUp(): void
    {
        parent::setUp();
        if (!is_dir(base_path('demo'))) {
            $this->markTestSkipped('demo/ client export files not present.');
        }
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
        Notification::fake();

        // Never touch the real bucket: fake the configured disk and drop StorageHelper's cached instance.
        Storage::fake(config('settings.storage_driver'));
        $ref = new \ReflectionClass(StorageHelper::class);
        $ref->setStaticPropertyValue('cachedDisk', null);

        // A fresh layout cache, so the AI path really runs.
        DB::table('resort_data_import_mappings')->delete();

        config(['services.openrouter.key' => 'test-key']);
        Http::fake(['*/chat/completions' => function (HttpRequest $request) {
            preg_match('/^File: (.+)$/m', $request['messages'][1]['content'], $m);
            [$type, $header, $columns, $group] = self::AI[$m[1]];
            return Http::response([
                'choices' => [['message' => ['content' => "```json\n" . json_encode(['type' => $type, 'header_row' => $header, 'columns' => $columns, 'group_column' => $group]) . "\n```"]]],
                'usage'   => ['total_tokens' => 700],
            ]);
        }]);

        DB::table('admins')->insert(['first_name' => 'T', 'last_name' => 'T', 'email' => 'rds-test@wisdom.test', 'password' => Hash::make('Secret#123'),
            'status' => 'active', 'type' => 'super', 'allow_login' => 1, 'two_factor_confirmed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs(Admin::where('email', 'rds-test@wisdom.test')->first(), 'admin');

        $this->resortId = DB::table('resorts')->insertGetId(['resort_name' => 'RDS Test Resort', 'resort_email' => 'rds@resort.test', 'status' => 'Active',
            'resort_id' => 'rdstest01', 'resort_prefix' => 'RDST', 'resort_it_email' => 'it@resort.test', 'no_of_users' => 0, 'created_at' => now(), 'updated_at' => now()]);
        foreach (['annual' => 'Annual Leave', 'sick' => 'Sick Leave', 'emergency' => 'Emergency Leave', 'paternity' => 'Paternity Leave',
                  'birthday' => 'Birthday Leave', 'rr' => 'Rest Relaxation Leave', 'unpaid' => 'Unpaid Leave', 'ph' => 'Public Holiday'] as $k => $type) {
            $this->category[$k] = DB::table('leave_categories')->insertGetId(['resort_id' => $this->resortId, 'leave_type' => $type, 'number_of_days' => 30,
                'is_paid' => $k === 'unpaid' ? 'unpaid' : 'paid', 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->shiftId = DB::table('shift_settings')->insertGetId(['resort_id' => $this->resortId, 'ShiftName' => 'Morning', 'StartTime' => '07:00',
            'EndTime' => '15:00', 'TotalHours' => '8:0']);
    }

    private function url(string $name, ...$params): string
    {
        return route("admin.resort_data_setup.$name", array_merge([$this->resortId], $params));
    }

    private function upload(array $names)
    {
        return $this->post($this->url('upload'), ['files' => array_map(
            fn ($n) => new UploadedFile(base_path("demo/$n"), $n, 'application/vnd.ms-excel', null, true), $names
        )]);
    }

    public function test_full_setup_flow_from_client_exports()
    {
        $this->get(route('admin.resort_data_setup.index'))->assertOk()->assertSee('RDS Test Resort');
        $this->get($this->url('show'))->assertOk()->assertSee('Upload files');

        // Upload: AI is asked once per file, every file is recognised.
        $res = $this->upload(array_keys(self::AI))->assertOk();
        foreach (self::AI as $name => [$type]) {
            $res->assertSee("{$name}: recognised as {$type} (AI, 700 tokens).", false);
        }
        Http::assertSentCount(7);
        $import = ResortDataImport::where('resort_id', $this->resortId)->firstOrFail();
        $files = collect($import->files)->keyBy('name');
        $this->assertSame(252, $files['staff list.xls']['count']);
        $this->assertSame(255, $files['June Attendance.xls']['count']);
        $this->assertSame(304, $files['positions.xls']['count']);
        $this->assertSame('2026-05-26', $files['June Attendance.xls']['facets']['period_guess']);
        Storage::disk(config('settings.storage_driver'))->assertExists($files['staff list.xls']['path']);

        // Same layout again: cache, no AI call, file replaced not duplicated.
        $this->upload(['staff list.xls'])->assertOk()->assertSee('staff list.xls: recognised as staff (saved layout, 0 AI tokens).', false);
        Http::assertSentCount(7);
        $this->assertCount(7, $import->fresh()->files);

        // Manual mapping save (same columns) is accepted and remembered as manual.
        $level = collect($import->fresh()->files)->firstWhere('name', 'level.xls');
        $this->post($this->url('mapping', $level['id']), ['type' => 'levels', 'header_row' => 9, 'columns' => ['levels' => ['name' => 1]]])
            ->assertOk()->assertSee('mapping saved');
        $this->assertSame('manual', DB::table('resort_data_import_mappings')->where('fingerprint', $level['mapping']['fingerprint'])->value('source'));

        // Defaults derived from the files: levels, roles, attendance codes.
        $show = $this->get($this->url('show'))->assertOk();
        $options = $show->viewData('options');
        $this->assertEquals(['EX-9' => '1', 'EX-8' => '1', 'HOD-6' => '2', 'MGR-5' => '4', 'MGR-4' => '4', 'SUP-3' => '5', 'LS-2' => '6', 'LS-1' => '6'],
            $options['level_ranks']);
        $this->assertSame('HUMAN RESOURCES', $options['roles']['hr']);
        $this->assertSame('FINANCE', $options['roles']['finance']);
        $this->assertSame('leave:' . $this->category['annual'], $options['codes']['AL']);
        $this->assertSame('present', $options['codes']['(blank)']);
        $this->assertSame('dayoff', $options['codes']['DO']);

        // Import stays locked until a clean validation.
        $show->assertSee('Import unlocks after a validation with no errors.');

        // Options: no shift yet → validation reports it and saves nothing.
        $form = ['email_domain' => 'rds-test.wisdom.local', 'level_ranks' => $options['level_ranks'], 'roles' => $options['roles'],
                 'codes' => $options['codes'], 'period_start' => $options['period_start'], 'shift_id' => ''];
        $this->post($this->url('options'), $form)->assertOk()->assertSee('Options saved.');
        $this->post($this->url('validate'))->assertOk()->assertSee('Choose the shift to attach to migrated attendance');
        $this->assertSame(0, DB::table('employees')->where('resort_id', $this->resortId)->count());

        $form['shift_id'] = $this->shiftId;
        $this->post($this->url('options'), $form)->assertOk();
        $this->post($this->url('validate'))->assertOk()->assertSee('Validation result (nothing saved)')->assertSee('Validation passed.');
        $report = $import->fresh()->report;
        $this->assertSame([], $report['errors']);
        $this->assertSame(252, $report['counts']['staff']['created']);
        $this->assertSame(0, DB::table('employees')->where('resort_id', $this->resortId)->count(), 'dry run must roll back');
        $this->assertSame(0, DB::table('resort_divisions')->where('resort_id', $this->resortId)->count());

        // Import needs a fresh identity confirmation.
        $this->post($this->url('import'))->assertRedirect(route('admin.reauth'));
        $this->assertSame(0, DB::table('employees')->where('resort_id', $this->resortId)->count());

        $res = $this->withSession(['admin_reauth_at' => time()])->post($this->url('import'))->assertOk()
            ->assertSee('Import finished.')->assertSee('New logins (252)');
        $credentials = collect($res->viewData('credentials'))->keyBy('emp_id');
        $import = $import->fresh();
        $this->assertSame('imported', $import->status);
        $this->assertSame(252, $import->report['credentials'], 'only the count is stored');
        $this->assertStringNotContainsString($credentials['RDST-0433']['password'], json_encode($import->report));
        $raw = DB::table('resort_data_imports')->where('id', $import->id)->value('credentials');
        $this->assertStringNotContainsString($credentials['RDST-0433']['password'], $raw, 'passwords are encrypted at rest');

        // A second pickup of a finished job (queue retry_after) does nothing.
        $before = $this->snapshot();
        (new RunResortDataImport($import->id))->handle();
        $this->assertSame($before, $this->snapshot());

        $this->assertImportedData($credentials);

        // The new account works on the web portal, with a forced password change.
        $this->post(route('admin.logout'));
        $login = $this->postJson(route('resort.login'), ['email' => 'rdst-0433@rds-test.wisdom.local', 'password' => $credentials['RDST-0433']['password']]);
        $login->assertJson(['success' => true]);
        $this->assertSame(1, (int) DB::table('resort_admins')->where('email', 'rdst-0433@rds-test.wisdom.local')->value('must_change_password'));
        auth('resort-admin')->logout();
        $this->actingAs(Admin::where('email', 'rds-test@wisdom.test')->first(), 'admin');

        // Passwords shown until the admin clears them.
        $this->get($this->url('show'))->assertSee('New logins (252)');
        $this->post($this->url('clear_credentials'))->assertOk()->assertSee('Temporary passwords cleared.')->assertDontSee('New logins');
        $this->assertNull(DB::table('resort_data_imports')->where('id', $import->id)->value('credentials'));

        // Re-import the same files: everything matched, nothing duplicated, no new logins.
        $before = $this->snapshot();
        $this->upload(array_keys(self::AI))->assertOk();
        Http::assertSentCount(7);
        $this->get($this->url('show'))->assertOk()->assertSee('rds-test.wisdom.local'); // options carried to the new batch
        $this->post($this->url('validate'))->assertOk()->assertSee('Validation passed.');
        $this->withSession(['admin_reauth_at' => time()])->post($this->url('import'))->assertOk()->assertSee('Import finished.')->assertDontSee('New logins');
        $this->assertSame($before, $this->snapshot());
        $this->assertSame(252, ResortDataImport::where('resort_id', $this->resortId)->latest('id')->first()->report['counts']['staff']['updated']);
    }

    public function test_employee_ids_must_be_unique_across_resorts()
    {
        $this->upload(['fusion division.xls', 'department.xls', 'staff list.xls'])->assertOk();
        $this->post($this->url('options'), ['email_domain' => 'rds-test.wisdom.local'])->assertOk();

        // Prefix shared with another resort → blocked.
        $taken = DB::table('resorts')->where('id', '!=', $this->resortId)->whereNotNull('resort_prefix')->where('resort_prefix', '!=', '')->first();
        DB::table('resorts')->where('id', $this->resortId)->update(['resort_prefix' => $taken->resort_prefix]);
        $this->post($this->url('validate'))->assertOk()->assertSee("Resort Prefix {$taken->resort_prefix} is also used by", false);

        // Unique prefix, but one resulting ID already exists in another resort → that row is blocked.
        DB::table('resorts')->where('id', $this->resortId)->update(['resort_prefix' => 'RDST']);
        DB::table('employees')->where('id', DB::table('employees')->where('resort_id', '!=', $this->resortId)->value('id'))->update(['Emp_id' => 'RDST-0433']);
        $this->post($this->url('validate'))->assertOk()->assertSee('Employee ID RDST-0433 already exists in another resort.');
        $this->assertCount(1, ResortDataImport::where('resort_id', $this->resortId)->first()->report['errors']);

    }

    public function test_work_runs_on_the_queue_and_locks_the_page()
    {
        Queue::fake();
        $this->upload(['level.xls'])->assertOk()->assertSee('queued')->assertSee('Reading…');
        Queue::assertPushed(RunResortDataImport::class, 1);
        $import = ResortDataImport::where('resort_id', $this->resortId)->firstOrFail();
        $this->assertSame(['analyse', 'queued'], [$import->job_action, $import->job_status]);

        $this->upload(['department.xls'])->assertOk()->assertSee('A job is still running');
        $this->post($this->url('validate'))->assertOk()->assertSee('A job is still running');
        Queue::assertPushed(RunResortDataImport::class, 1);
        $this->assertCount(1, $import->fresh()->files);

        // The worker picks it up.
        (new RunResortDataImport($import->id))->handle();
        $import = $import->fresh();
        $this->assertSame('done', $import->job_status);
        $this->assertSame('levels', $import->files[0]['mapping']['type']);
        $this->get($this->url('show'))->assertOk()->assertSee('level.xls: recognised as levels');

        // A job that died mid-run is reported, not left spinning forever.
        $import->forceFill(['job_status' => 'running', 'job_started_at' => now()->subHours(2)])->save();
        $this->get($this->url('show'))->assertOk()->assertSee('The job stopped unexpectedly');
        $this->assertSame('failed', $import->fresh()->job_status);
    }

    private function assertImportedData($credentials): void
    {
        $r = $this->resortId;
        $this->assertSame(18, DB::table('resort_divisions')->where('resort_id', $r)->count());
        $this->assertSame(18, DB::table('resort_departments')->where('resort_id', $r)->count());
        $this->assertSame(47, DB::table('resort_sections')->where('resort_id', $r)->count());
        $this->assertSame(259, DB::table('resort_positions')->where('resort_id', $r)->count());
        $this->assertSame(252, DB::table('employees')->where('resort_id', $r)->count());
        // Every new employee got its categorized folder row (invariant #6).
        $this->assertSame(252, DB::table('filemangement_systems')->where('resort_id', $r)->where('Folder_Type', 'categorized')->count());

        $emp = fn ($id) => DB::table('employees')->where('resort_id', $r)->where('Emp_id', $id)->first();
        $this->assertNull($emp('0433'), 'imported IDs carry the resort prefix');
        $gm = $emp('RDST-1220');
        $this->assertSame(['8', 8], [$gm->rank, $gm->main_rank]);
        $this->assertSame('GENERAL MANAGER', DB::table('resort_positions')->where('id', $gm->Position_id)->value('position_title'));
        $this->assertSame(8, DB::table('resort_positions')->where('id', $gm->Position_id)->value('Rank'));

        $ram = $emp('RDST-0433'); // prefix added, leading zero kept, d/m/Y hire date, nationality mapped
        $this->assertSame(['2', 2, '2020-01-15', 'Sri Lankan', '0', 'Mr'], [$ram->rank, $ram->main_rank, $ram->joining_date, $ram->nationality, $ram->religion, $ram->title]);
        $admin = DB::table('resort_admins')->where('id', $ram->Admin_Parent_id)->first();
        $this->assertSame(['rdst-0433@rds-test.wisdom.local', 'RAJAKUMARA', 'MANICKAM', 'male', 'sub', 0], [$admin->email, $admin->first_name, $admin->last_name, $admin->gender, $admin->type, (int) $admin->is_master_admin]);
        $this->assertTrue(Hash::check($credentials['RDST-0433']['password'], $admin->password));

        // Role departments: EXCOM/HOD/MGR in HR → main_rank 3, Finance → 7; others keep their level.
        $roles = DB::table('employees as e')->join('resort_departments as d', 'd.id', '=', 'e.Dept_id')->where('e.resort_id', $r)
            ->whereIn('d.name', ['HUMAN RESOURCES', 'FINANCE'])->get(['d.name', 'e.rank', 'e.main_rank']);
        foreach ($roles as $x) {
            $expected = in_array((int) $x->rank, [1, 2, 4], true) ? ($x->name === 'FINANCE' ? 7 : 3) : (int) $x->rank;
            $this->assertSame($expected, $x->main_rank, "{$x->name} rank {$x->rank}");
        }
        $this->assertTrue($roles->contains(fn ($x) => $x->main_rank === 3) && $roles->contains(fn ($x) => $x->main_rank === 7));

        // Attendance vs the client's own totals columns (AL, DO, UL=NP, OL=PH+BT+…, P=blank) for every imported employee.
        $book = IOFactory::load(base_path('demo/June Attendance.xls'));
        $sheet = $book->getSheet(0)->toArray(null, false, true, false);
        $book->disconnectWorksheets();
        $ids = DB::table('employees')->where('resort_id', $r)->pluck('id', 'Emp_id');
        $expected = ['P' => 0, 'BT' => 0, 'DO' => 0, 'AL' => 0, 'NP' => 0];
        foreach ($sheet as $row) {
            if (!ctype_digit((string) $row[0]) || !isset($ids['RDST-' . trim($row[1])])) {
                continue;
            }
            $expected['AL'] += (int) $row[35];
            $expected['DO'] += (int) $row[36];
            $expected['NP'] += (int) $row[37];
            $expected['P'] += (int) $row[39];
            $expected['BT'] += count(array_filter(array_slice($row, 4, 31), fn ($c) => trim((string) $c) === 'BT'));
        }
        $status = DB::table('parent_attendaces')->where('resort_id', $r)->selectRaw('Status, count(*) c')->groupBy('Status')->pluck('c', 'Status');
        $this->assertSame($expected['P'] + $expected['BT'], $status['Present']);
        $this->assertSame($expected['DO'], $status['DayOff']);
        $leaveDays = fn ($cat) => (int) DB::table('employees_leaves')->where('resort_id', $r)->where('leave_category_id', $this->category[$cat])->sum('total_days');
        $this->assertSame($expected['AL'], $leaveDays('annual'));
        $this->assertSame($expected['NP'], $leaveDays('unpaid'));
        $this->assertSame((int) $status['FullDayLeave'], (int) DB::table('employees_leaves')->where('resort_id', $r)->sum('total_days'));
        $this->assertSame(0, DB::table('employees_leaves')->where('resort_id', $r)->where('status', '!=', 'Approved')->count());
        $this->assertSame(DB::table('parent_attendaces')->where('resort_id', $r)->count(), DB::table('duty_roster_entries')->where('resort_id', $r)->count());
        // 1326 joined 21/06/2026: no attendance before the hire date (the sheet's own P total is 5).
        $late = $emp('RDST-1326');
        $this->assertSame('2026-06-21', $late->joining_date);
        $this->assertSame(5, DB::table('parent_attendaces')->where('Emp_id', $late->id)->count());
        $this->assertSame(['07:00', '15:00', '08:00'], array_values((array) DB::table('parent_attendaces')->where('resort_id', $r)
            ->where('Status', 'Present')->first(['CheckingTime', 'CheckingOutTime', 'DayWiseTotalHours'])));
        $this->assertSame($expected['P'] + $expected['BT'], DB::table('child_attendaces as c')->join('parent_attendaces as p', 'p.id', '=', 'c.Parent_attd_id')->where('p.resort_id', $r)->count());
        $this->assertSame('05/26/2026 - 06/25/2026', DB::table('duty_rosters')->where('resort_id', $r)->value('ShiftDate'));
    }

    private function snapshot(): array
    {
        $r = $this->resortId;
        return [
            DB::table('resort_divisions')->where('resort_id', $r)->count(),
            DB::table('resort_departments')->where('resort_id', $r)->count(),
            DB::table('resort_sections')->where('resort_id', $r)->count(),
            DB::table('resort_positions')->where('resort_id', $r)->count(),
            DB::table('employees')->where('resort_id', $r)->count(),
            DB::table('resort_admins')->where('resort_id', $r)->count(),
            DB::table('duty_rosters')->where('resort_id', $r)->count(),
            DB::table('duty_roster_entries')->where('resort_id', $r)->count(),
            DB::table('parent_attendaces')->where('resort_id', $r)->count(),
            DB::table('child_attendaces as c')->join('parent_attendaces as p', 'p.id', '=', 'c.Parent_attd_id')->where('p.resort_id', $r)->count(),
            DB::table('employees_leaves')->where('resort_id', $r)->count(),
            DB::table('filemangement_systems')->where('resort_id', $r)->count(),
        ];
    }
}
