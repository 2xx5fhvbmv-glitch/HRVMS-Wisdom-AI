<?php

namespace App\Exports;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use DB;
use Auth;
use Common;



use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class SurveyResultExport implements FromCollection, WithHeadings, WithCustomStartCell, WithMapping
{
    protected $surveyName, $totalRespondents, $responseRate, $avgCompletionTime, $data;

    public function __construct($surveyName, $totalRespondents, $responseRate, $avgCompletionTime, $data)
    {
        $this->surveyName = $surveyName;
        $this->totalRespondents = $totalRespondents;
        $this->responseRate = $responseRate;
        $this->avgCompletionTime = $avgCompletionTime;
        $this->data = collect($data); // Ensure it's a collection
    }

   

   

    public function view(): View
    {
        $survey = \App\Models\Survey::with('questions.answers')->findOrFail($this->survey_id);

        return view('exports.exportsurveydetails', [
            'survey' => $survey
        ]);
    }

}

