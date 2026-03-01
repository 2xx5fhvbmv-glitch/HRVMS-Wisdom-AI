<?php

namespace App\Exports;
use Auth;
use DB;
use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;


class PaymentsExport implements FromCollection, WithHeadings
{
    protected $month;
    protected $year;
    protected $startDate;
    protected $endDate;
    protected $searchTerm;
    public $shopkeeper;

    public function __construct($month = null, $year = null, $startDate = null, $endDate = null, $searchTerm = null)
    {
        $this->month = $month;
        $this->year = $year;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->searchTerm = $searchTerm;
        $this->shopkeeper = Auth::guard('shopkeeper')->user();
    }

    public function collection()
    {
        $query = Payment::join('employees as e', 'e.id', '=', 'payments.emp_id')
            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->join('products as p', 'p.id', '=', 'payments.product_id')
            ->where('payments.shopkeeper_id', $this->shopkeeper->id)
            ->whereIn('payments.status', ['Paid', 'Partial Paid', 'Pending', 'Pending Consent', 'Consented', 'Rejected']);

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('payments.purchased_date', [$this->startDate, $this->endDate]);
        } else {
            if ($this->month) {
                $query->whereMonth('payments.purchased_date', $this->month);
            }
            if ($this->year) {
                $query->whereYear('payments.purchased_date', $this->year);
            }
        }

        if ($this->searchTerm) {
            $term = $this->searchTerm;
            $query->where(function ($q) use ($term) {
                $q->where('p.price', 'LIKE', "%{$term}%")
                  ->orWhere('p.name', 'LIKE', "%{$term}%")
                  ->orWhere('payments.quantity', 'LIKE', "%{$term}%")
                  ->orWhere('ra.first_name', 'LIKE', "%{$term}%")
                  ->orWhere('ra.last_name', 'LIKE', "%{$term}%")
                  ->orWhere('payments.status', 'LIKE', "%{$term}%");
            });
        }

        return $query->orderBy('payments.updated_at', 'DESC')
            ->select([
                'payments.order_id',
                'e.Emp_id',
                DB::raw("CONCAT(ra.first_name, ' ', ra.last_name) as name"),
                'payments.purchased_date',
                'p.name as product_name',
                'payments.quantity',
                'payments.price',
                DB::raw("CASE WHEN p.currency_type = 'MVR' THEN 'MVR' ELSE 'Dollar' END as currency_type"),
                'payments.status'
            ])->get();
    }

    public function headings(): array
    {
        return ['Order ID', 'Emp ID', 'Name', 'Purchase Date', 'Product', 'Quantity', 'Price', 'Currency', 'Status'];
    }
}
