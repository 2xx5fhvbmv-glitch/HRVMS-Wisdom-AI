<?php

namespace App\Exports;
use Auth;
use DB;
use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;


class PaymentsExport implements FromCollection,WithHeadings
{
    protected $month;
    protected $year;
    public $shopkeeper;


    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year = $year;
        $this->shopkeeper = Auth::guard('shopkeeper')->user();

    }

    public function collection()
    {
        $query = Payment::join('employees as e', 'e.id', '=', 'payments.emp_id')
            ->join('resort_admins as ra', 'ra.id', '=', 'e.Admin_Parent_id')
            ->join('products as p', 'p.id', '=', 'payments.product_id')
            ->where('payments.shopkeeper_id', $this->shopkeeper->id);
            // ->where(function ($query) {
            //     $query->where('payments.status', '!=','Paid')
            //         ->orWhere('payments.status', '!=','Pending');
            // });

        if ($this->month) {
            $query->whereMonth('purchased_date', $this->month);
        }
        if ($this->year) {
            $query->whereYear('purchased_date', $this->year);
        }

        return $query->select([
            'payments.order_id',
            'e.Emp_id',
            DB::raw("CONCAT(ra.first_name, ' ', ra.last_name) as name"),
            'payments.purchased_date',
            'p.name as product_name',
            'payments.quantity',
            'payments.price',
            'payments.status'
        ])->get();
    }

    public function headings(): array
    {
        return ['Order ID', 'Emp ID', 'Name', 'Purchase Date', 'Product', 'Quantity', 'Price','Status'];
    }
}
