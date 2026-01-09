<?php namespace App\Exports;

use App\Models\CodeOfCounduct;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DisciplineryCodeOfConduct implements FromCollection, WithHeadings, WithEvents
{
    protected $resort_id;

    public function __construct()
    {
        $this->resort_id = Auth::guard('resort-admin')->user()->resort_id;
    }

    public function headings(): array
    {
        return ['Category', 'Offense', 'Action', 'Severity Level'];
    }

    public function collection()
    {
        // Fetch data and join necessary tables
        $records = CodeOfCounduct::join('disciplinary_categories_models', 'disciplinary_categories_models.id', '=', 'code_of_counducts.Deciplinery_cat_id')
            ->join('offenses_models', 'offenses_models.id', '=', 'code_of_counducts.Offenses_id')
            ->join('action_stores', 'action_stores.id', '=', 'code_of_counducts.Action_id')
            ->join('severity_stores', 'severity_stores.id', '=', 'code_of_counducts.Severity_id')
            ->where('code_of_counducts.resort_id', $this->resort_id)
            ->get([
                'disciplinary_categories_models.DisciplinaryCategoryName as Category',
                'offenses_models.OffensesName as Offense',
                'action_stores.ActionName as Action',
                'severity_stores.SeverityName as SeverityLevel'
            ]);

        return new Collection($records);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $data = $sheet->toArray();
                $highestRow = count($data);

                // Merge Category Cells if Values are the Same
                $mergeStart = null;
                $previousCategory = null;

                for ($row = 2; $row <= $highestRow; $row++) {
                    $currentCategory = $sheet->getCell("A$row")->getValue();

                    if ($currentCategory === $previousCategory) {
                        if ($mergeStart === null) {
                            $mergeStart = $row - 1;
                        }
                    } else {
                        if ($mergeStart !== null) {
                            $sheet->mergeCells("A{$mergeStart}:A" . ($row - 1));
                            $mergeStart = null;
                        }
                    }
                    $previousCategory = $currentCategory;
                }

                // Handle last merge set
                if ($mergeStart !== null) {
                    $sheet->mergeCells("A{$mergeStart}:A{$highestRow}");
                }

                // Apply styling: Bold headers and center alignment
                $sheet->getStyle('A1:D1')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // Apply border to all cells
                $sheet->getStyle("A1:D{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Auto-size columns
                foreach (range('A', 'D') as $columnID) {
                    $sheet->getColumnDimension($columnID)->setAutoSize(true);
                }
            },
        ];
    }
}
