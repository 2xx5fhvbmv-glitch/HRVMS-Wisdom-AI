<?php
namespace App\Http\Controllers\Resorts;
use DB;
use Auth;
use Carbon\Carbon;
use App\Models\Resort;
use Illuminate\Http\Request;
use App\Models\ResortReports;
use App\Helpers\Common;
use App\Exports\ReportExport;
use Barryvdh\DomPDF\Facade\PDF;
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
            $reports = ResortReports::where('resort_id', $r->resort_id)->orderBy("created_at","desc")->get();
        
            return datatables()->of($reports)
            ->addColumn('action', function ($row) 
            {
                $route = route('reports.show', base64_encode($row->id));
                return '<a target="_blank" href="'. $route .'" class="btn btn-success btn-sm edit-division" data-id="' . $row->id . '"><i class="fa fa-eye"></i></button>';
            })
            ->editColumn('name', function ($row) 
            {
                return  $row->name;
            })
            ->editColumn('CareatedAt', function ($row) 
            {
                return  $row->created_at->format('d/m/Y');
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
         $page_title = 'Create Report';
        $tables = DB::select('SHOW TABLES');
        return view('resorts.reports.create', compact('tables', 'page_title'));
    }
    public function store(Request $request)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'table_name' => 'required|string',
            'columns' => 'required|array',
            'related_columns' => 'nullable|array', 
            'filters' => 'nullable|array',
            'from_date' => 'nullable|date_format:Y-m-d',
            'to_date' => 'nullable|date_format:Y-m-d',
        ]);

        $relationTables = [];
        if (!empty($validated['related_columns'])) 
        {
            foreach ($validated['related_columns'] as $table => $columns) 
            {
                if (!empty($columns)) {
                    $relationTables[$table] = ['columns' => $columns];
                }
            }
        }
        $query_params = [
            'table' => $validated['table_name'],
            'columns' => $validated['columns'],
            'relation_tables' => $relationTables, 
            'filters' => $validated['filters'] ?? [],
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
        $columns = $report->query_params['columns'] ?? [];
        $relation_tables = $report->query_params['relation_tables'] ?? [];
        $data = $this->runReport($request->report_id,$request->todate,$request->formdate);
        
        $html =  view('resorts.renderfiles.ReportFilterData', compact('report', 'columns', 'data', 'relation_tables'))->render();
        
        return response()->json([
            'html' => $html,
            "columns" => count($data),
        ]);
    }
    private function runReport($id,$fromDate,$toDate)
    {

        $report = ResortReports::where('id', $id)->first();
        $queryParams = $report->query_params;
        $tableName = $queryParams['table'];
        $columns = $queryParams['columns'] ?? [];
        $relationTables = $queryParams['relation_tables'] ?? [];
        $filters = $queryParams['filters'] ?? [];
        $query = DB::table($tableName)
            ->where('resort_id', $this->resort->resort_id);
        
        $query->select("$tableName.*");
        
        $formDate = Carbon::createFromFormat('d/m/Y', $fromDate)->format('Y-m-d');
        $toDate = Carbon::createFromFormat('d/m/Y', $toDate)->format('Y-m-d');
        

        $query->whereBetween('created_at', [Carbon::parse($toDate)->format("Y-m-d"),Carbon::parse($formDate)->format("Y-m-d"),]);
        $mainRecords = $query->get();
        if (!empty($filters)) {
            foreach ($filters as $filter) {
                if (!empty($filter) && isset($filter['field'], $filter['operator'], $filter['value'])) {
                    $field = $filter['field'];
                    $operator = $filter['operator'];
                    $value = $filter['value'];
                    
                    switch ($operator) {
                        case 'equals':
                            $query->where($field, '=', $value);
                            break;
                        case 'contains':
                            $query->where($field, 'LIKE', "%{$value}%");
                            break;
                        case 'greater_than':
                            $query->where($field, '>', $value);
                            break;
                        case 'less_than':
                            $query->where($field, '<', $value);
                            break;
                        
                    }
                }
            }
        }
        $mainRecords = $query->get();
        $results = [];
        
        foreach ($mainRecords as $record) {
            $recordArray = (array)$record;
            

            foreach ($relationTables as $relationTable => $relationDetails) {
                $relationColumns = $relationDetails['columns'] ?? [];
                $foreignKey = $this->findForeignKeyForTable($tableName, $relationTable);
                if ($foreignKey && isset($record->$foreignKey)) {
                    $relatedRecord = DB::table($relationTable)
                        ->where('id', $record->$foreignKey)
                        ->where('resort_id', $this->resort->resort_id)
                        ->first();
                    
                    if ($relatedRecord) {
                        $relatedArray = [];
                        foreach ($relationColumns as $column) {
                            if (isset($relatedRecord->$column)) 
                            {
                                $relatedArray[$column] = $relatedRecord->$column;
                            }
                        }
                        $recordArray[$relationTable] = $relatedArray;
                    }
                }
            }
            
            $results[] = $recordArray;
        }
        
        return $results;
    }
    
    private function findForeignKeyForTable($mainTable, $relationTable)
    {
        $foreignKeys = $this->getTableForeignKeys($mainTable);

        foreach ($foreignKeys as $column => $referencedTable) 
        {
            if ($referencedTable === $relationTable) 
            {
                return $column;
            }
        }
        return null;
    }
    public function getTableColumns(Request $request)
    {
        $tableName = $request->input('table');
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
        $data = $this->runReport(base64_decode($request->report_id),$request->Form_todate,$request->Form_formdate);
        return $this->exportReport($data, $report, $request->format);
    }
    private function exportReport($data, $report, $format)
    {
        $data = $this->preprocessDataForExport($data);
        
        switch ($format) 
        {
            case 'pdf':
                $pdf = PDF::loadView('resorts.reports.pdf', [
                    'report' => $report,
                    'data' => $data,
                    'columns' => $report->query_params['columns'],
                    'relation_tables' => $report->query_params['relation_tables'] ?? [],
                ]);
                return $pdf->download($report->name.'.pdf');
                
            case 'excel':
                return Excel::download(
                    new ReportExport(
                        $data, 
                        $report->query_params['columns'],
                        $report->query_params['relation_tables'] ?? []
                    ), 
                    $report->name.'.xlsx'
                );
                
            case 'csv':
                return Excel::download(
                    new ReportExport(
                        $data, 
                        $report->query_params['columns'], 
                        $report->query_params['relation_tables'] ?? []
                    ), 
                    $report->name.'.csv'
                );
                
            default:
                abort(400, 'Unsupported export format');
        }
    }
    private function preprocessDataForExport($data)
    {
        $processedData = [];
        
        foreach ($data as $row) {
            $processedRow = is_array($row) ? $row : (array)$row;
            
            foreach ($processedRow as $key => $value) {
                if (is_string($value) && strpos($value, '{') === 0 && substr($value, -1) === '}') {
                    $jsonData = json_decode($value, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $processedRow[$key] = json_encode($jsonData, JSON_PRETTY_PRINT);
                    }
                }
            }
            
            $processedData[] = $processedRow;
        }
        
        return $processedData;
    }
    public function edit($id)
    {
        $report = ResortReports::findOrFail(base64_decode($id));
        $tables = DB::select('SHOW TABLES');
        $queryParams = $report->query_params;
        $columns = $queryParams['columns'] ?? [];
        $relationTables = $queryParams['relation_tables'] ?? [];
        return view('resorts.reports.edit', compact('report', 'tables', 'columns', 'relationTables'));
    }

    public function AiInsideReport(Request $request)
    {
        $report_id  = base64_decode($request->report_id);
        $todate     = $request->todate;
        $formdate   = $request->formdate;
        $report = ResortReports::findOrFail($report_id);

        $columns = $report->query_params['columns'] ?? [];
        $relation_tables = $report->query_params['relation_tables'] ?? [];

        // Get report data (assumed array of arrays or collection of arrays)
        $reportData = $this->runReport($report_id, $todate, $formdate);

        // Prepare report info to embed in each row
        $reportInfo = [
            "name" => $report->name,
            "resort_id" => $this->resort->resort_id,
            "description" => $report->description,
            "created_at" => $report->created_at->format('d/m/Y')
        ];

        $formattedData = [];

        foreach ($reportData as $row) 
        {
            $formattedRow = [];

            foreach ($columns as $column) 
            {
                if (isset($relation_tables[$column])) 
                {
                    // Related table column
                    $relatedData = $row[$column] ?? null;

                    if ($relatedData) {
                        $relColumns = $relation_tables[$column]['columns'] ?? [];
                        $relationInfo = [];

                        foreach ($relColumns as $relColumn) {
                            if (in_array(strtolower($relColumn), ['rank', 'benefit_grid_level'])) {
                                $eligibility = config('settings.eligibilty');
                                $relationInfo[$relColumn] = $eligibility[$relatedData[$relColumn]] ?? $relatedData[$relColumn] ?? "N/A";
                            } else {
                                $relationInfo[$relColumn] = $relatedData[$relColumn] ?? "N/A";
                            }
                        }
                        $formattedRow[$column] = $relationInfo;
                    } else {
                        $formattedRow[$column] = "N/A";
                    }
                } 
                else 
                {
                    // Regular column
                    if (isset($row[$column])) {
                        if (in_array(strtolower($column), ['rank', 'benefit_grid_level'])) {
                            $eligibility = config('settings.eligibilty');
                            $formattedRow[$column] = $eligibility[$row[$column]] ?? $row[$column];
                        } else {
                            $formattedRow[$column] = $row[$column];
                        }
                    } else {
                        $formattedRow[$column] = "N/A";
                    }
                }
            }
            $formattedRow['report'] = $reportInfo;
            $formattedData[] = $formattedRow;
        }

        $requestData = [
            'resort_data' => [
                'additionalProp1' => [
                    'columns' => $columns,
                    'data' => $formattedData
                ]
            ]
        ];
        
        if(!isset($report->AiInsights))
        {
              $jsonData = json_encode($requestData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $url = env('AI_Report_fetch_URL');
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $jsonData,
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'Accept: application/json',
                        'Content-Length: ' . strlen($jsonData)
                    ],
                ]);
                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);
                if($err) 
                {
                    return response()->json(['status' => false, 'message' =>  $err]);
                } 
                $AI_Data = json_decode($response, true); 
                if($AI_Data)
                {
                    $report->AiInsights = $AI_Data;
                    $report->save();

                }
            
        }
        else
        {
            $AI_Data = $report->AiInsights;
        }

        $Analysis  = json_decode($AI_Data);
        if($Analysis)
        {

              $AI_Data =$Analysis->analysis;
        }
        else
        {
            $AI_Data = '';
        }
        

    return response()->json(['status' => true, 'data' => $AI_Data]);
    }

}   