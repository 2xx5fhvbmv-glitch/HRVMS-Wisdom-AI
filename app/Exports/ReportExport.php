<?php
namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ReportExport implements FromView
{
    protected $data;
    protected $columns;
    protected $relationTables;

    public function __construct($data, $columns, $relationTables = [])
    {
        $this->data = $data;
        $this->columns = $columns;
        $this->relationTables = $relationTables;
    }

    public function view(): View
    {
        return view('resorts.reports.CsvOrExcel', [ 
            'data' => $this->data,
            'columns' => $this->columns,
            'relation_tables' => $this->relationTables,
        ]);
    }
}