<?php

namespace App\Exports;

use App\Models\Payment;
use DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ResortShopkeeperPaymentsExport implements FromCollection, WithHeadings
{
    protected $shopkeeperId;
    protected $startDate;
    protected $endDate;
    protected $searchTerm;

    public function __construct($shopkeeperId, $startDate = null, $endDate = null, $searchTerm = null)
    {
        $this->shopkeeperId = $shopkeeperId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->searchTerm = $searchTerm;
    }

    public function collection()
    {
        $query = Payment::join('employees as e', 'e.id', '=', 'payments.emp_id')
            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->join('products as p', 'p.id', '=', 'payments.product_id')
            ->where('payments.shopkeeper_id', $this->shopkeeperId)
            ->whereIn('payments.status', ['Consented', 'Paid', 'Partial Paid']);

        if (!empty($this->startDate) && !empty($this->endDate)) {
            $query->whereBetween('payments.purchased_date', [$this->startDate, $this->endDate]);
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
