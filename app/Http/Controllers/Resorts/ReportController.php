<?php
namespace App\Http\Controllers\Resorts;
use DB;
use Auth;
use Schema;
use Carbon\Carbon;
use App\Models\Resort;
use Illuminate\Http\Request;
use App\Models\ResortReports;
use App\Helpers\Common;
use App\Exports\ReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
class ReportController extends Controller
{
    public function __construct()
    {
        $this->resort = Auth::guard('resort-admin')->user();
        if(!$this->resort) return;
    }
    public function index(Request $request)
    {

        $r = $this->resort;
        if($request->ajax())
        {
            $dateFormat = Common::getDateFormateFromSettings();
            $reports = ResortReports::where('resort_id', $r->resort_id)->orderBy("created_at","desc")->get();

            return datatables()->of($reports)
            ->addColumn('action', function ($row)
            {
                $viewRoute = route('reports.show', base64_encode($row->id));
                return '<a target="_blank" href="'. $viewRoute .'" class="btn btn-success btn-sm me-1" title="View"><i class="fa fa-eye"></i></a>'
                    . '<button type="button" class="btn btn-danger btn-sm report-delete-btn" data-id="'. $row->id .'" title="Delete"><i class="fa fa-trash"></i></button>';
            })
            ->editColumn('name', function ($row) 
            {
                return  $row->name;
            })
            ->editColumn('CareatedAt', function ($row) use ($dateFormat)
            {
                return  $row->created_at->format($dateFormat);
            })
            ->editColumn('description', function ($row) 
            {
                return  $row->description;
            })
            ->rawColumns(['name', 'description','action'])
            ->make(true);
        }
        $page_title = 'Reports';
        return view('resorts.reports.index', compact('page_title'));
    }
    public function create()
    {
         if(Common::checkRouteWisePermission('resort.report.index',config('settings.resort_permissions.create')) == false){
            return abort(403, 'Unauthorized access');
         }
         $page_title = 'Custom Report';
        // HR picks by Module -> Entity -> curated business Fields. They never see
        // raw table or column names; the catalog below is the only vocabulary.
        $catalog = $this->reportFieldCatalog();
        return view('resorts.reports.create', compact('catalog', 'page_title'));
    }

    /**
     * The curated semantic catalog the UI renders, filtered to entities whose
     * base table actually exists. Shape for the front end:
     *   [ Module => [ Entity => [ 'table' => t, 'fields' => [ 'Label', ... ] ] ] ]
     * Only field labels are exposed — never columns, joins or related tables.
     */
    private function reportFieldCatalog(): array
    {
        $catalog = [];
        foreach ((array) config('report_fields', []) as $module => $entities) {
            foreach ((array) $entities as $entity => $def) {
                $table = $def['table'] ?? null;
                if ($table && Schema::hasTable($table)) {
                    $catalog[$module][$entity] = [
                        'table'  => $table,
                        'fields' => array_keys($def['fields'] ?? []),
                    ];
                }
            }
        }
        return $catalog;
    }

    /**
     * Resolve a single Module/Entity to its raw definition from config, or null.
     * This is the gatekeeper: anything the builder runs must come from here.
     */
    private function findEntityDef(?string $module, ?string $entity): ?array
    {
        $def = config("report_fields.$module.$entity");
        if (!is_array($def) || empty($def['table']) || !Schema::hasTable($def['table'])) {
            return null;
        }
        return $def;
    }
    public function store(Request $request)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'module' => 'required|string',
            'entity' => 'required|string',
            'fields' => 'required|array|min:1',
            'filters' => 'nullable|array',
            'from_date' => 'nullable|date_format:Y-m-d',
            'to_date' => 'nullable|date_format:Y-m-d',
        ]);

        // Reject anything not exposed through the curated semantic catalog.
        $def = $this->findEntityDef($validated['module'], $validated['entity']);
        if (!$def) {
            return response()->json(['success' => false, 'message' => 'That data source is not available for reporting.'], 422);
        }

        // Keep only fields that exist in this entity, preserving the catalog order
        // so report columns are always laid out consistently.
        $allowedFields = array_keys($def['fields']);
        $selectedFields = array_values(array_intersect($allowedFields, $validated['fields']));
        if (empty($selectedFields)) {
            return response()->json(['success' => false, 'message' => 'Please choose at least one valid field.'], 422);
        }

        // Filters may only reference fields the report actually includes.
        $filters = [];
        foreach ($validated['filters'] ?? [] as $filter) {
            if (!empty($filter['field']) && in_array($filter['field'], $selectedFields, true)
                && isset($filter['operator'], $filter['value']) && $filter['value'] !== '') {
                $filters[] = [
                    'field'    => $filter['field'],
                    'operator' => $filter['operator'],
                    'value'    => $filter['value'],
                ];
            }
        }

        $query_params = [
            'module'  => $validated['module'],
            'entity'  => $validated['entity'],
            'table'   => $def['table'],
            'fields'  => $selectedFields,
            'filters' => $filters,
        ];
        $report               =  new ResortReports();
        $report->name         = $validated['name'];
        $report->description  = $validated['description'];
        $report->from_date    = $validated['from_date'];
        $report->to_date      = $validated['to_date'];
        $report->query_params =  $query_params;
        $report->resort_id    = $this->resort->resort_id;
        $report->save();
        return response()->json([
            'success' => true,
            'message' => 'Report created successfully',
            'redirect_url' => route('resort.report.index')
        ]);
    }
    public function show($id, Request $request)
    {
        if(Common::checkRouteWisePermission('resort.report.index',config('settings.resort_permissions.view')) == false){
            return abort(403, 'Unauthorized access');
        }
    
        $page_title = 'Report Details';
        $report = ResortReports::findOrFail(base64_decode($id));
        $form_date =  $report->from_date ? Carbon::parse($report->from_date)->format('d/m/Y') : '';
        $to_date =  $report->to_date ? Carbon::parse($report->to_date)->format('d/m/Y') : '';
        return view('resorts.reports.show',compact('report','form_date','to_date','page_title'));
    }
    public function FetchReportData(Request $request)
    {

        $report = ResortReports::findOrFail($request->report_id);
        $result  = $this->runReport($request->report_id, $request->todate, $request->formdate);
        $columns = $result['columns'];
        $data    = $result['rows'];

        $html =  view('resorts.renderfiles.ReportFilterData', compact('report', 'columns', 'data'))->render();

        return response()->json([
            'html' => $html,
            "columns" => count($data),
        ]);
    }
    /**
     * Run a saved report and return resolved, display-ready data.
     * Shape: [ 'columns' => [ 'Field Label', ... ], 'rows' => [ [ 'Field Label' => value ], ... ] ].
     *
     * Everything is driven by the curated config/report_fields definition, so a
     * report can only ever read tables/columns that were deliberately exposed.
     * Business labels are resolved here (names from related tables, rank/grade
     * mappings, date formatting) — the views just print label => value.
     */
    private function runReport($id, $fromDate, $toDate): array
    {
        $report = ResortReports::where('id', $id)->first();
        $params = $report->query_params ?? [];

        $def = $this->findEntityDef($params['module'] ?? null, $params['entity'] ?? null);
        // Legacy reports (old column-based query_params) have no entity definition.
        if (!$def) {
            return ['columns' => [], 'rows' => []];
        }

        $table       = $def['table'];
        $employeeFk  = $def['employee_fk'] ?? null;
        $fieldDefs   = $def['fields'];
        // Honour the saved field order, but only for fields still in the catalog.
        $labels = array_values(array_filter(
            $params['fields'] ?? [],
            fn ($label) => isset($fieldDefs[$label])
        ));
        $filters = $params['filters'] ?? [];

        // ---- Base query: resort + department scoping + created_at date range ----
        $query = DB::table($table);
        if (Schema::hasColumn($table, 'resort_id')) {
            $query->where("$table.resort_id", $this->resort->resort_id);
        }

        $scopedDeptIds = Common::getScopedDepartmentIds();
        $scopedEmpIds  = Common::getPerformanceScopedEmpIds();
        if (is_array($scopedDeptIds)) {
            if (Schema::hasColumn($table, 'Dept_id')) {
                $query->whereIn("$table.Dept_id", $scopedDeptIds);
            } elseif (Schema::hasColumn($table, 'department_id')) {
                $query->whereIn("$table.department_id", $scopedDeptIds);
            } elseif (is_array($scopedEmpIds)) {
                if (Schema::hasColumn($table, 'Emp_main_id')) {
                    $query->whereIn("$table.Emp_main_id", $scopedEmpIds);
                } elseif (Schema::hasColumn($table, 'emp_id')) {
                    $query->whereIn("$table.emp_id", $scopedEmpIds);
                } elseif (Schema::hasColumn($table, 'employee_id')) {
                    $query->whereIn("$table.employee_id", $scopedEmpIds);
                } elseif ($table === 'employees' && Schema::hasColumn($table, 'id')) {
                    $query->whereIn("$table.id", $scopedEmpIds);
                }
            }
        }

        // Be resilient about both formats (d/m/Y and already-normalised Y-m-d)
        $parseFlexible = function ($value, $endOfDay = false) {
            if (empty($value)) return null;
            foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $fmt) {
                try {
                    $c = Carbon::createFromFormat($fmt, $value);
                    return $endOfDay ? $c->endOfDay() : $c->startOfDay();
                } catch (\Exception) {}
            }
            try {
                $c = Carbon::parse($value);
                return $endOfDay ? $c->endOfDay() : $c->startOfDay();
            } catch (\Exception) {}
            return null;
        };
        $from = $parseFlexible($fromDate, false);
        $to   = $parseFlexible($toDate, true);
        if ($from && $to && Schema::hasColumn($table, 'created_at')) {
            if ($from->greaterThan($to)) { [$from, $to] = [$to, $from]; }
            $query->whereBetween("$table.created_at", [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')]);
        }

        $baseRows = $query->get()->map(fn ($r) => (array) $r)->all();
        if (empty($baseRows)) {
            return ['columns' => $labels, 'rows' => []];
        }

        // ---- Batch-load everything the selected fields need (no N+1) ----
        $selectedDefs = array_intersect_key($fieldDefs, array_flip($labels));

        $needsEmployee = false;
        foreach ($selectedDefs as $fd) {
            if (isset($fd['employee_col']) || isset($fd['employee_name']) || isset($fd['employee_lookup'])) {
                $needsEmployee = true;
                break;
            }
        }

        // Map of employees keyed by employees.id. For the Employees entity the
        // base row IS the employee, so no extra fetch is needed.
        $employees = [];
        if ($needsEmployee && $employeeFk) {
            $empIds = array_filter(array_unique(array_column($baseRows, $employeeFk)));
            if (!empty($empIds)) {
                $employees = DB::table('employees')->whereIn('id', $empIds)
                    ->get()->keyBy('id')->map(fn ($r) => (array) $r)->all();
            }
        }
        $employeeFor = function (array $baseRow) use ($employeeFk, $employees) {
            if (!$employeeFk) return $baseRow;           // base row is the employee
            $fk = $baseRow[$employeeFk] ?? null;
            return $fk !== null ? ($employees[$fk] ?? null) : null;
        };

        // Lookup tables keyed by id. We union every id needed for a given table.
        $lookupMaps = [];
        $collectLookup = function ($table, $id) use (&$lookupMaps) {
            if ($table && $id !== null && $id !== '') {
                $lookupMaps[$table][$id] = true;
            }
        };
        $needAdmin = false;
        foreach ($baseRows as $baseRow) {
            $emp = $employeeFor($baseRow);
            foreach ($selectedDefs as $fd) {
                if (isset($fd['lookup'])) {
                    $collectLookup($fd['lookup'], $baseRow[$fd['fk']] ?? null);
                } elseif (isset($fd['employee_lookup']) && $emp) {
                    $collectLookup($fd['employee_lookup'], $emp[$fd['fk']] ?? null);
                } elseif (isset($fd['employee_name'])) {
                    $needAdmin = true;
                    if ($emp) { $collectLookup('resort_admins', $emp['Admin_Parent_id'] ?? null); }
                }
            }
        }
        foreach ($lookupMaps as $lt => $idSet) {
            $rows = DB::table($lt)->whereIn('id', array_keys($idSet))
                ->get()->keyBy('id')->map(fn ($r) => (array) $r)->all();
            $lookupMaps[$lt] = $rows;
        }

        // ---- Resolve each base row into a label => display-value array ----
        $eligibility = config('settings.eligibilty', []);
        $dateFormat  = Common::getDateFormateFromSettings();
        $format = function ($fd, array $baseRow) use ($employeeFor, $lookupMaps, $eligibility, $dateFormat) {
            if (isset($fd['employee_name'])) {
                $emp = $employeeFor($baseRow);
                $admin = $emp ? ($lookupMaps['resort_admins'][$emp['Admin_Parent_id'] ?? null] ?? null) : null;
                if (!$admin) return 'N/A';
                $name = trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? ''));
                return $name !== '' ? $name : 'N/A';
            }
            if (isset($fd['lookup'])) {
                $rec = $lookupMaps[$fd['lookup']][$baseRow[$fd['fk']] ?? null] ?? null;
                $v = $rec[$fd['name']] ?? null;
                return ($v === null || $v === '') ? 'N/A' : $v;
            }
            if (isset($fd['employee_lookup'])) {
                $emp = $employeeFor($baseRow);
                $rec = $emp ? ($lookupMaps[$fd['employee_lookup']][$emp[$fd['fk']] ?? null] ?? null) : null;
                $v = $rec[$fd['name']] ?? null;
                return ($v === null || $v === '') ? 'N/A' : $v;
            }

            // Plain / employee column with optional cast.
            if (isset($fd['employee_col'])) {
                $srcRow = $employeeFor($baseRow);
                $v = $srcRow[$fd['employee_col']] ?? null;
            } else {
                $srcRow = $baseRow;
                $v = $baseRow[$fd['col']] ?? null;
            }
            if ($v === null || $v === '') return 'N/A';

            $cast = $fd['cast'] ?? null;
            if ($cast === 'eligibility') {
                return $eligibility[$v] ?? $v;
            }
            if ($cast === 'date') {
                try { return Carbon::parse($v)->format($dateFormat); } catch (\Exception) { return $v; }
            }
            if ($cast === 'money') {
                if (!is_numeric($v)) { return $v; }
                // Salaries can be held in USD or MVR per employee — convert
                // via the shared helper (same as every other money report)
                // instead of appending the raw currency code as text.
                $currency = isset($fd['currency_col']) ? ($srcRow[$fd['currency_col']] ?? 'USD') : 'USD';
                return Common::formatCurrency((float) $v, $currency);
            }
            return $v;
        };

        $rows = [];
        foreach ($baseRows as $baseRow) {
            $out = [];
            foreach ($labels as $label) {
                $out[$label] = $format($selectedDefs[$label], $baseRow);
            }
            $rows[] = $out;
        }

        // ---- Apply filters on the resolved display values ----
        if (!empty($filters)) {
            $rows = array_values(array_filter($rows, function ($row) use ($filters) {
                foreach ($filters as $filter) {
                    $field = $filter['field'] ?? null;
                    if ($field === null || !array_key_exists($field, $row)) { return false; }
                    $cell  = (string) $row[$field];
                    $value = (string) $filter['value'];
                    switch ($filter['operator'] ?? '') {
                        case 'equals':
                            if (strcasecmp($cell, $value) !== 0) return false;
                            break;
                        case 'contains':
                            if (stripos($cell, $value) === false) return false;
                            break;
                        case 'greater_than':
                            if (!(floatval($cell) > floatval($value))) return false;
                            break;
                        case 'less_than':
                            if (!(floatval($cell) < floatval($value))) return false;
                            break;
                    }
                }
                return true;
            }));
        }

        return ['columns' => $labels, 'rows' => $rows];
    }
    public function getTableColumns(Request $request)
    {
        $tableName = $request->input('table');
        // Only expose columns for tables in the curated catalog allow-list.
        if (!in_array($tableName, $this->allowedReportTables(), true)) {
            return response()->json(['data' => ['parent_columns' => [], 'related_tables' => []]]);
        }
        $columns = collect(DB::getSchemaBuilder()->getColumnListing($tableName))->sort()->values();
        $foreignKeys = $this->getTableForeignKeys($tableName);
        
        $resortName = $this->resort->resort->resort_name;
        $Prefix = implode('', array_map(fn($word) => strtoupper($word[0]), explode(' ', $resortName)));
        
        $Parent_table = [];
        $Child_table = [];
        
        foreach($columns as $c)
        {
            if ($this->isVarcharOrEnum($tableName, $c)) 
            {
                $Parent_table[] = [
                    'original' => $c,
                    'formatted' => $Prefix.'-'.ucfirst(str_replace('_', ' ', $c))
                ];
            }
            if(isset($foreignKeys[$c]))
            {
                $foreignTableName = $foreignKeys[$c];
                $foreignTableColumns = collect(DB::getSchemaBuilder()->getColumnListing($foreignTableName))
                    ->sort()
                    ->values();
                $foreignVarcharEnumColumns = [];
                foreach ($foreignTableColumns as $foreignColumn) 
                {
                    if ($this->isVarcharOrEnum($foreignTableName, $foreignColumn)) 
                    {
                        $foreignVarcharEnumColumns[]=['original' => $foreignColumn,'formatted' => $Prefix.'-'.ucfirst(str_replace('_', ' ', $foreignColumn))];
                    }
                }
                $Child_table[] = [
                    'original_foreign_key' => $c, 
                    'formatted_foreign_key' => $Prefix.'-'. ucfirst(str_replace('_', ' ', $c)),
                    'referenced_table' => $foreignTableName,
                    'formatted_table_name' => $Prefix.'-'. ucfirst(str_replace('_', ' ', $foreignTableName)),
                    'columns' => $foreignVarcharEnumColumns
                ];
            }
        }
            return response()->json(['data' => ['parent_columns' => $Parent_table,'related_tables' => $Child_table]]);
    }
    private function getTableForeignKeys($tableName)
    {
        $database = DB::getDatabaseName();
        
        $foreignKeys = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select('COLUMN_NAME', 'REFERENCED_TABLE_NAME')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $tableName)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->get();
        
        $result = [];
        
        foreach ($foreignKeys as $fk) {
            $result[$fk->COLUMN_NAME] = $fk->REFERENCED_TABLE_NAME;
        }
        
        return $result;
    }
    private function isVarcharOrEnum($tableName, $columnName)
    {
        $database = DB::getDatabaseName();
        $columnInfo = DB::table('information_schema.COLUMNS')->select('DATA_TYPE', 'COLUMN_TYPE')->where('TABLE_SCHEMA', $database)->where('TABLE_NAME', $tableName)->where('COLUMN_NAME', $columnName)->first();
        
        if (!$columnInfo) 
        {
            return false;
        }
        
        // Check if data_type is varchar or if column_type starts with 'enum'
        return true;
    }
    private function columnExistsInTable($tableName, $columnName)
    {
        $columns = DB::getSchemaBuilder()->getColumnListing($tableName);
        return in_array($columnName, $columns);
    }
    private function isTableName($tableName)
    {
        $tables = DB::select('SHOW TABLES');
        $tables = array_map('current', json_decode(json_encode($tables), true));
        return in_array($tableName, $tables);
    }
    public function export(Request $request)
    {
        
        $report = ResortReports::findOrFail(base64_decode($request->report_id));
        $result = $this->runReport(base64_decode($request->report_id),$request->Form_todate,$request->Form_formdate);
        return $this->exportReport($result, $report, $request->format);
    }
    private function exportReport($result, $report, $format)
    {
        $columns = $result['columns'];
        $data    = $result['rows'];

        // Every export carries the WAI Insights too. Use the cached analysis if
        // present, otherwise generate it now (gracefully '' if the AI is down).
        $insights = $this->aiAnalysisText($report, $columns, $data);

        switch ($format)
        {
            case 'pdf':
                $pdf = Pdf::loadView('resorts.reports.pdf', [
                    'report'       => $report,
                    'data'         => $data,
                    'columns'      => $columns,
                    'insightsHtml' => $insights !== '' ? $this->markdownToHtml($insights) : '',
                ]);
                return $pdf->download($report->name.'.pdf');

            case 'excel':
                return Excel::download(new ReportExport($data, $columns, $this->markdownToLines($insights)), $report->name.'.xlsx');

            case 'csv':
                return Excel::download(new ReportExport($data, $columns, $this->markdownToLines($insights)), $report->name.'.csv');

            default:
                abort(400, 'Unsupported export format');
        }
    }

    /**
     * Resolve the WAI Insights analysis text for a report: return the cached
     * analysis when present, otherwise call the AI service once and cache it.
     * Returns '' if no insight could be produced (e.g. AI service unreachable),
     * so callers can degrade gracefully.
     */
    private function aiAnalysisText($report, array $columns, array $rows): string
    {
        $extractAnalysis = function ($value) {
            if (is_array($value))  { return (string) ($value['analysis'] ?? ''); }
            if (is_object($value)) { return (string) ($value->analysis ?? ''); }
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                return is_array($decoded) ? (string) ($decoded['analysis'] ?? '') : '';
            }
            return '';
        };

        // Cached insight wins (also what the "WAI Insights" button stored).
        $analysisText = empty($report->AiInsights) ? '' : $extractAnalysis($report->AiInsights);
        if ($analysisText !== '') {
            return $analysisText;
        }

        $reportInfo = [
            "name"        => $report->name,
            "resort_id"   => $this->resort->resort_id,
            "description" => $report->description,
            "created_at"  => $report->created_at->format('d/m/Y'),
        ];
        $formattedData = [];
        foreach ($rows as $row) {
            $row['report'] = $reportInfo;
            $formattedData[] = $row;
        }
        $requestData = ['resort_data' => ['additionalProp1' => ['columns' => $columns, 'data' => $formattedData]]];

        $jsonData = json_encode($requestData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => env('AI_Report_fetch_URL'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Content-Length: ' . strlen($jsonData),
            ],
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return '';
        }
        $analysisText = $extractAnalysis($response);
        if ($analysisText !== '') {
            $report->AiInsights = json_encode(['analysis' => $analysisText]);
            $report->save();
        }
        return $analysisText;
    }

    /**
     * Minimal Markdown -> HTML for the insights block in PDF exports. Handles the
     * subset the AI emits: #/##/### headings, **bold**, and - / * bullet lists.
     */
    private function markdownToHtml(string $md): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $md);
        $html = '';
        $inList = false;
        $closeList = function () use (&$html, &$inList) {
            if ($inList) { $html .= "</ul>\n"; $inList = false; }
        };
        $inline = function ($text) {
            $text = e($text);
            $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
            return $text;
        };
        foreach ($lines as $line) {
            $trim = trim($line);
            if ($trim === '') { $closeList(); continue; }
            if (preg_match('/^(#{1,6})\s+(.*)$/', $trim, $m)) {
                $closeList();
                $level = min(strlen($m[1]) + 1, 6); // shift so # -> h2 etc.
                $html .= "<h{$level}>" . $inline($m[2]) . "</h{$level}>\n";
            } elseif (preg_match('/^[-*]\s+(.*)$/', $trim, $m)) {
                if (!$inList) { $html .= "<ul>\n"; $inList = true; }
                $html .= '<li>' . $inline($m[1]) . "</li>\n";
            } else {
                $closeList();
                $html .= '<p>' . $inline($trim) . "</p>\n";
            }
        }
        $closeList();
        return $html;
    }

    /**
     * Strip Markdown markers to plain text lines for spreadsheet/CSV exports.
     */
    private function markdownToLines(string $md): array
    {
        if ($md === '') { return []; }
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', $md) as $line) {
            $t = trim($line);
            $t = preg_replace('/^#{1,6}\s+/', '', $t);   // headings
            $t = preg_replace('/^[-*]\s+/', '• ', $t);    // bullets
            $t = preg_replace('/\*\*(.+?)\*\*/s', '$1', $t); // bold
            $out[] = $t;
        }
        return $out;
    }
    public function edit($id)
    {
        $page_title = 'Edit Report';
        $report = ResortReports::findOrFail(base64_decode($id));
        // Same curated vocabulary as create — never expose raw tables/columns.
        $catalog = $this->reportFieldCatalog();
        $params  = $report->query_params ?? [];
        $selected = [
            'module' => $params['module'] ?? '',
            'entity' => $params['entity'] ?? '',
            'fields' => $params['fields'] ?? [],
        ];
        return view('resorts.reports.edit', compact('report', 'catalog', 'selected', 'page_title'));
    }

    public function destroy($id)
    {
        $decoded = is_numeric($id) ? (int) $id : base64_decode($id);
        $report = ResortReports::where('resort_id', $this->resort->resort_id)->find($decoded);
        if (!$report) {
            return response()->json(['success' => false, 'message' => 'Report not found'], 404);
        }
        $report->delete();
        return response()->json(['success' => true, 'message' => 'Report deleted successfully.']);
    }

    public function AiInsideReport(Request $request)
    {
        $report_id  = base64_decode($request->report_id);
        $todate     = $request->todate;
        $formdate   = $request->formdate;
        $report = ResortReports::findOrFail($report_id);

        // Data is already resolved to business labels -> display values.
        $result  = $this->runReport($report_id, $todate, $formdate);

        // Cached insight wins; otherwise call the AI service once and cache it.
        $analysisText = $this->aiAnalysisText($report, $result['columns'], $result['rows']);

        return response()->json(['status' => true, 'data' => $analysisText]);
    }

}   