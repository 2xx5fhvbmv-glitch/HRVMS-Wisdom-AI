<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class PaymentRequestExport implements FromCollection, WithHeadings, WithEvents
{
    protected $paymentRequest;
    protected $paymentRequestChildren;

    public function __construct($paymentRequest, $paymentRequestChildren)
    {
        $this->paymentRequest = $paymentRequest;
        $this->paymentRequestChildren = $paymentRequestChildren;
    }

    public function collection()
    {
        $data = [];

        // First Row: Request Info
        $data[] = [
            'Request ID: ' . $this->paymentRequest->Requestd_id,
            'Request Date: ' . Carbon::parse($this->paymentRequest->Request_date)->format('d/m/Y'),
            'Status: ' . $this->paymentRequest->Status
        ];

        // Empty row for spacing
        $data[] = [''];

        foreach ($this->paymentRequestChildren as $child) {

            $data[] = ['Employee Name: ' . (!empty($child->Emp_name) ? $child->Emp_name . ' Employee ID ' . $child->Emp_id : 'N/A')];
            $data[] = ['Department : ' . (!empty($child->Department_name) ? $child->Department_name . ' Position  ' . $child->Position_name : 'N/A')];
            $extras = json_decode($child->Data, true); // assuming 'Data' is JSON with all the info
    

            // Helper to format line
            $line = function ($title, $last, $due, $amt) {
                return "$title Last Paid: $last | Due Date: $due | Amount MVR: " . number_format($amt, 2);
            };

            $data[] = [$line('WorkPermit', $extras['LastWorkPermit'] ?? 'N/A', $extras['WorkPermitExpiry'] ?? 'N/A', $extras['WorkPermitAmt'] ?? 0)];
            $data[] = [$line('Insurance', $extras['LastInsurance'] ?? 'N/A', $extras['InsuranceExpiry'] ?? 'N/A', $extras['InsuranceAmt'] ?? 0)];
            $data[] = [$line('Work Permit Medical Test Fee', $extras['LastMedical'] ?? 'N/A', $extras['MedicalExpiry'] ?? 'N/A', $extras['MedicalAmt'] ?? 0)];
            $data[] = [$line('Quota Slot', $extras['LastQuota'] ?? 'N/A', $extras['QuotaExpiry'] ?? 'N/A', $extras['QuotaAmt'] ?? 0)];
            $data[] = [$line('Visa', $extras['LastVisa'] ?? 'N/A', $extras['VisaExpiry'] ?? 'N/A', $extras['VisaAmt'] ?? 0)];

            // Add spacing row between employees
            $data[] = [''];
        }

        return new Collection($data);
    }

    public function headings(): array
    {
        return ['Visa Payment Request Export']; // Title Header
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Make title bold
                $event->sheet->getStyle('A1')->getFont()->setBold(true);
                // Set column A to auto-size
                $event->sheet->getDelegate()->getColumnDimension('A')->setAutoSize(true);
            },
        ];
    }
}
