<?php
namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ReportExport implements FromView
{
    protected $data;
    protected $columns;
    protected $insights;

    public function __construct($data, $columns, $insights = [])
    {
        $this->data = $data;
        $this->columns = $columns;
        $this->insights = $insights;
    }

    public function view(): View
    {
        return view('resorts.reports.CsvOrExcel', [
            'data' => $this->data,
            'columns' => $this->columns,
            'insights' => $this->insights,
        ]);
    }
}
