<?php
namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ReportExport implements FromView
{
    protected $data;
    protected $columns;

    public function __construct($data, $columns)
    {
        $this->data = $data;
        $this->columns = $columns;
    }

    public function view(): View
    {
        return view('resorts.reports.CsvOrExcel', [
            'data' => $this->data,
            'columns' => $this->columns,
        ]);
    }
}
