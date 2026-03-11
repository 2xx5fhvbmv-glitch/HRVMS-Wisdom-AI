<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Auth;
use App\Models\StoreConsolidateBudgetParent;
use App\Models\StoreConsolidateBudgetChild;
use App\Models\ResortBudgetCost;
use DB;

class ConsolidateBudgetImport implements ToCollection
{
    public $headers;
    public $resort;
    public $datas;

    function __construct($data)
    {
        $this->datas = $data;
        $this->resort = Auth::guard('resort-admin')->user();
    }

    public function collection(Collection $collection)
    {
        // Fetch the headers
        $allHeaders = $collection->first()->toArray();
        $tableData = ResortBudgetCost::where('resort_id', $this->resort->resort_id)->get()->pluck('particulars')->toArray();

            $Newfilds = [
                            'Division', // Added Division heading
                            'Department',
                            'Position',
                            'Rank',
                            'NATION',
                            'NoOfPosition',
                            'Current Salary',
                            'Proposed Salary',


                        ];


        $expectedHeaders = array_merge($Newfilds, $tableData);
        $expectedCount   = count($expectedHeaders);

        // The exported template includes hidden helper columns (DivisionID, DepartmentID,
        // DivisionName, DepartmentName) which inflate the column count. Truncate to the
        // expected number of columns so those hidden extras are ignored.
        $this->headers = array_slice($allHeaders, 0, $expectedCount);

        if(count($this->headers) ==  $expectedCount)
        {
            $data = $collection->slice(1)->values();

            $cleanData = [];

            foreach ($data as $row)
            {
                // Truncate row to match expected column count (ignores hidden helper columns)
                $rowArray = array_slice($row->toArray(), 0, $expectedCount);
                if(count($rowArray) === count($this->headers) )
                {

                    // Remove null and empty values from the row
                    $filteredRow = array_filter($rowArray, function ($value) {
                        return !is_null($value) && $value !== '';
                    });

                    // Check if the row is valid (matches header count) and is not empty
                    if (!empty($filteredRow) && $this->isRowValid($filteredRow, $this->headers)) {
                        $cleanData[] = $filteredRow;
                    }
                }

            }




            DB::beginTransaction();
            try {
                $parent = StoreConsolidateBudgetParent::updateOrCreate(
                    [
                        'Resort_id' => $this->resort->resort_id,
                        'Year' => $this->datas['Year'],
                    ],
                    [  
                        'file' => $this->datas['file'],
                    ]
                );

                StoreConsolidateBudgetChild::updateOrCreate(
                    ["Parent_SCB_id" => $parent->id],
                    [
                        "Parent_SCB_id" => $parent->id,
                        'header' => json_encode($this->headers),
                        'Data' => json_encode($cleanData),
                    ]
                );

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
            }
        } else {
            throw new \Exception(
                'The uploaded file has ' . count($this->headers) . ' column(s) but ' . $expectedCount . ' are expected. '
                . 'Please download and use the latest template.'
            );
        }
    }




    private function isRowValid($row, $headers)
    {
        return count($row) === count($headers);
    }
}
