<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;

class SurveyResultExport implements FromCollection, WithHeadings, WithCustomStartCell, WithMapping, WithEvents
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

    public function collection(): Collection
    {
        return $this->data;
    }

    public function headings(): array
    {
        return ['ID', 'Participant Name', 'Question', 'Answer'];
    }

    public function map($row): array
    {
        return [
            $row['id'],
            $row['ParticipantName'],
            $row['Question'],
            $row['Ans'],
        ];
    }

    // Data table starts below the summary block written in registerEvents().
    public function startCell(): string
    {
        return 'A6';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $sheet->setCellValue('A1', 'Survey Name:');
                $sheet->setCellValue('B1', $this->surveyName);
                $sheet->setCellValue('A2', 'Total Respondents:');
                $sheet->setCellValue('B2', $this->totalRespondents);
                $sheet->setCellValue('A3', 'Response Rate:');
                $sheet->setCellValue('B3', $this->responseRate . '%');
                $sheet->setCellValue('A4', 'Avg Completion Time:');
                $sheet->setCellValue('B4', $this->avgCompletionTime);
            },
        ];
    }
}
