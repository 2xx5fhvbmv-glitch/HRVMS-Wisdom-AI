<?php

namespace App\Exports;

use App\Models\ResortDivision;
use App\Models\ResortDepartment;
use App\Models\ResortPosition;
use App\Models\ResortSection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportEmployeeDropdownSheet implements FromArray, WithTitle, WithEvents, WithHeadings
{
    protected array $data = [];
    protected array $headings = [];
    protected array $divisionMapping = [];
    protected array $departmentMapping = [];
    protected array $positionMapping = [];
    protected array $sectionMapping = [];

    public function __construct(protected int $resortId)
    {
        $this->prepareData();
    }

    public function title(): string
    {
        return 'SourceData';
    }

    private function safeName(string $name, int $id = null): string
    {
        if ($id !== null) {
            return 'ID_' . $id;
        }
        
        $safe = trim($name);
        if (empty($safe)) {
            return '_empty';
        }
        
        $safe = str_replace([
            ' ', '&', '/', '-', '.', '(', ')', '[', ']', '+', '=', 
            '@', '#', '$', '%', '^', '*', '!', '~', '`', '{', '}', 
            '|', '\\', ':', ';', '"', "'", '<', '>', '?', ','
        ], '_', $safe);
        
        $safe = preg_replace('/[^A-Za-z0-9_]/', '', $safe);
        $safe = preg_replace('/_+/', '_', $safe);
        $safe = trim($safe, '_');
        
        if (preg_match('/^[0-9]/', $safe)) {
            $safe = '_' . $safe;
        }

        if (empty($safe)) {
            return '_cleaned';
        }
        
        return $safe;
    }

    private function prepareData(): void
    {
        $divisions = ResortDivision::where('resort_id', $this->resortId)->where('status', 'active')
            ->orderBy('name')
            ->get();

        $departments = ResortDepartment::where('resort_id', $this->resortId)->where('status', 'active')
            ->orderBy('name')
            ->get();
            
        $positions = ResortPosition::where('resort_id', $this->resortId)->where('status', 'active')
            ->orderBy('position_title')
            ->get();
           
        $sections = ResortSection::where('resort_id', $this->resortId)->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Static dropdowns - Display name and code for divisions
        $this->data['Divisions'] = $divisions->map(function($division) {
            return $division->name . ' (' . $division->code . ')';
        })->values()->toArray();

        $this->data['Genders'] = ['male', 'female', 'other'];
        
        $this->data['Nationalities'] = config('settings.nationalities') ?? [
            "Afghan",
			"Albanian",
			"Algerian",
			"American",
			"Andorran",
			"Angolan",
			"Antiguan and Barbudan",
			"Argentine",
			"Armenian",
			"Australian",
			"Austrian",
			"Azerbaijani",
			"Bahamian",
			"Bahraini",
			"Bangladeshi",
			"Barbadian",
			"Belarusian",
			"Belgian",
			"Belizean",
			"Beninese",
			"Bhutanese",
			"Bolivian",
			"Bosnian and Herzegovinian",
			"Botswanan",
			"Brazilian",
			"British",
			"Bruneian",
			"Bulgarian",
			"Burkinabé",
			"Burmese",
			"Burundian",
			"Cambodian",
			"Cameroonian",
			"Canadian",
			"Cape Verdean",
			"Central African",
			"Chadian",
			"Chilean",
			"Chinese",
			"Colombian",
			"Comorian",
			"Congolese (Congo-Brazzaville)",
			"Congolese (Congo-Kinshasa)",
			"Costa Rican",
			"Croatian",
			"Cuban",
			"Cypriot",
			"Czech",
			"Danish",
			"Djiboutian",
			"Dominican",
			"Dutch",
			"East Timorese",
			"Ecuadorean",
			"Egyptian",
			"Emirati",
			"Equatorial Guinean",
			"Eritrean",
			"Estonian",
			"Eswatini",
			"Ethiopian",
			"Fijian",
			"Filipino",
			"Finnish",
			"French",
			"Gabonese",
			"Gambian",
			"Georgian",
			"German",
			"Ghanaian",
			"Greek",
			"Grenadian",
			"Guatemalan",
			"Guinean",
			"Bissau-Guinean",
			"Guyanese",
			"Haitian",
			"Honduran",
			"Hungarian",
			"Icelander",
			"Indian",
			"Indonesian",
			"Iranian",
			"Iraqi",
			"Irish",
			"Israeli",
			"Italian",
			"Ivorian",
			"Jamaican",
			"Japanese",
			"Jordanian",
			"Kazakhstani",
			"Kenyan",
			"Kiribati",
			"Kuwaiti",
			"Kyrgyzstani",
			"Lao",
			"Latvian",
			"Lebanese",
			"Liberian",
			"Libyan",
			"Liechtensteiner",
			"Lithuanian",
			"Luxembourger",
			"Malagasy",
			"Malawian",
			"Malaysian",
			"Maldivian",
			"Malian",
			"Maltese",
			"Marshallese",
			"Mauritanian",
			"Mauritian",
			"Mexican",
			"Micronesian",
			"Moldovan",
			"Monacan",
			"Mongolian",
			"Montenegrin",
			"Moroccan",
			"Mozambican",
			"Namibian",
			"Nauruan",
			"Nepalese",
			"New Zealander",
			"Nicaraguan",
			"Nigerien",
			"Nigerian",
			"North Korean",
			"North Macedonian",
			"Norwegian",
			"Omani",
			"Pakistani",
			"Palauan",
			"Palestinian",
			"Panamanian",
			"Papua New Guinean",
			"Paraguayan",
			"Peruvian",
			"Polish",
			"Portuguese",
			"Qatari",
			"Romanian",
			"Russian",
			"Rwandan",
			"Saint Kitts and Nevisian",
			"Saint Lucian",
			"Saint Vincentian",
			"Samoan",
			"San Marinese",
			"São Toméan",
			"Saudi Arabian",
			"Senegalese",
			"Serbian",
			"Seychellois",
			"Sierra Leonean",
			"Singaporean",
			"Slovak",
			"Slovenian",
			"Solomon Islander",
			"Somali",
			"South African",
			"South Korean",
			"South Sudanese",
			"Spanish",
			"Sri Lankan",
			"Sudanese",
			"Surinamese",
			"Swedish",
			"Swiss",
			"Syrian",
			"Tajikistani",
			"Tanzanian",
			"Thai",
			"Togolese",
			"Tongan",
			"Trinidadian and Tobagonian",
			"Tunisian",
			"Turkish",
			"Turkmen",
			"Tuvaluan",
			"Ugandan",
			"Ukrainian",
			"Uruguayan",
			"Uzbekistani",
			"Vanuatuan",
			"Vatican Citizen",
			"Venezuelan",
			"Vietnamese",
			"Yemeni",
			"Zambian",
			"Zimbabwean"
        ];

        // Create mapping for divisions and departments
        foreach ($divisions as $division) {
            $divId = 'DIV_' . $division->id;
            $divisionDisplayName = $division->name . ' (' . $division->code . ')';
            $this->divisionMapping[$divisionDisplayName] = $divId;
            
            $divisionDepartments = $departments->where('division_id', $division->id);
            
            // Display department name and code
            $deptNames = $divisionDepartments->map(function($department) {
                return $department->name . ' (' . $department->code . ')';
            })->values()->toArray();
            
            if (!empty($deptNames)) {
                $this->data[$divId . '_depts'] = $deptNames;
            }

            // Process each department
            foreach ($divisionDepartments as $department) {
                $deptId = 'DEPT_' . $department->id;
                $departmentDisplayName = $department->name . ' (' . $department->code . ')';
                $this->departmentMapping[$departmentDisplayName] = $deptId;
                
                // Get positions for this department - display with code if available
                $deptPositions = $positions->where('dept_id', $department->id);
                $posNames = $deptPositions->map(function($position) {
                    if (!empty($position->position_code)) {
                        return $position->position_title . ' (' . $position->position_code . ')';
                    }
                    return $position->position_title;
                })->values()->toArray();
                
                // Get sections for this department - display with code if available
                $deptSections = $sections->where('dept_id', $department->id);
                $secNames = $deptSections->map(function($section) {
                    if (!empty($section->section_code)) {
                        return $section->name . ' (' . $section->section_code . ')';
                    }
                    return $section->name;
                })->unique()->values()->toArray();
                
                // Store positions and sections using department ID
                if (!empty($posNames)) {
                    $this->data[$deptId . '_positions'] = $posNames;
                }
                if (!empty($secNames)) {
                    $this->data[$deptId . '_sections'] = $secNames;
                }

                // Store position mapping for lookup
                foreach ($posNames as $posName) {
                    $this->positionMapping[$departmentDisplayName][] = $posName;
                }

                // Store section mapping for lookup
                foreach ($secNames as $secName) {
                    $this->sectionMapping[$departmentDisplayName][] = $secName;
                }
            }
        }

        // Add mapping data to sheet for VLOOKUP
        // Division mapping
        $divMapData = [];
        foreach ($this->divisionMapping as $name => $id) {
            $divMapData[] = [$name, $id];
        }
        if (!empty($divMapData)) {
            $this->data['DivisionMap_Name'] = array_column($divMapData, 0);
            $this->data['DivisionMap_ID'] = array_column($divMapData, 1);
        }

        // Department mapping
        $deptMapData = [];
        foreach ($this->departmentMapping as $name => $id) {
            $deptMapData[] = [$name, $id];
        }
        if (!empty($deptMapData)) {
            $this->data['DepartmentMap_Name'] = array_column($deptMapData, 0);
            $this->data['DepartmentMap_ID'] = array_column($deptMapData, 1);
        }

        // Create position mapping for departments (department display name -> positions)
        $positionMapData = [];
        foreach ($this->positionMapping as $deptName => $positions) {
            foreach ($positions as $posName) {
                $positionMapData[] = [$deptName, $posName];
            }
        }
        
        if (!empty($positionMapData)) {
            $this->data['PositionMap_Department'] = array_column($positionMapData, 0);
            $this->data['PositionMap_Position'] = array_column($positionMapData, 1);
        }

        // Create section mapping for departments (department display name -> sections)
        $sectionMapData = [];
        foreach ($this->sectionMapping as $deptName => $sections) {
            foreach ($sections as $secName) {
                $sectionMapData[] = [$deptName, $secName];
            }
        }
        
        if (!empty($sectionMapData)) {
            $this->data['SectionMap_Department'] = array_column($sectionMapData, 0);
            $this->data['SectionMap_Section'] = array_column($sectionMapData, 1);
        }
    }

    public function headings(): array
    {
        $this->headings = array_keys($this->data);
        return $this->headings;
    }

    public function array(): array
    {
        // Transpose the data to fit into columns correctly
        $maxRows = !empty($this->data) ? max(array_map('count', $this->data)) : 0;
        $rows = [];

        for ($i = 0; $i < $maxRows; $i++) {
            $row = [];
            foreach ($this->headings as $heading) {
                $row[] = $this->data[$heading][$i] ?? '';
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $worksheetTitle = $sheet->getTitle();
                $colIndex = 1;

                foreach ($this->headings as $name) {
                    $itemCount = count($this->data[$name]);
                    if ($itemCount > 0) {
                        $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                        $fullRangeAddress = "'{$worksheetTitle}'!\${$colLetter}\$2:\${$colLetter}\$" . ($itemCount + 1);

                        try {
                            // Add the named range to the workbook
                            $sheet->getParent()->addNamedRange(
                                new NamedRange($name, $sheet, $fullRangeAddress)
                            );
                        } catch (\Exception $e) {
                            \Log::warning("Could not create named range '{$name}': " . $e->getMessage());
                        }
                    }
                    $colIndex++;
                }

                // Hide this data sheet from the user
                $sheet->setSheetState('veryHidden');
            },
        ];
    }
}