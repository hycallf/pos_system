<?php

namespace Modules\People\DataTables;


use Modules\People\Entities\Customer;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\DB;

class CustomersDataTable extends DataTable
{

    public function dataTable($query) {
        return datatables()
            ->eloquent($query)
            ->addColumn('total_sales', function ($data) {
                // Format angka sebagai mata uang
                return format_currency($data->total_sales);
            })
            ->addColumn('total_paid', function ($data) {
                // Format angka sebagai mata uang
                return format_currency($data->total_paid);
            })
            ->addColumn('action', function ($data) {
                return view('people::customers.partials.actions', compact('data'));
            });
    }

    public function query(Customer $model) {
        // Query ini akan menghitung total penjualan dan pembayaran untuk setiap pelanggan
        return $model->newQuery()
            ->select('customers.*',
                DB::raw('(SELECT SUM(total_amount) FROM sales WHERE sales.customer_id = customers.id) as total_sales'),
                DB::raw('(SELECT SUM(paid_amount) FROM sales WHERE sales.customer_id = customers.id) as total_paid')
            );
    }

    public function html() {
        return $this->builder()
            ->setTableId('customers-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom("<'row'<'col-md-3'l><'col-md-5 mb-2'B><'col-md-4'f>> .
                                       'tr' .
                                 <'row'<'col-md-5'i><'col-md-7 mt-2'p>>")
            ->orderBy(0) // Default sorting berdasarkan kolom pertama (Nama)
            ->buttons(
                Button::make('excel')->text('<i class="bi bi-file-earmark-excel-fill"></i> Excel'),
                Button::make('print')->text('<i class="bi bi-printer-fill"></i> Print'),
                Button::make('reset')->text('<i class="bi bi-x-circle"></i> Reset'),
                Button::make('reload')->text('<i class="bi bi-arrow-repeat"></i> Reload')
            );
    }

    protected function getColumns() {
        return [
            Column::make('customer_name')->title('Name'),
            Column::make('customer_phone')->title('Phone'),
            Column::make('customer_email')->title('Email'),
            Column::make('country')->title('Country'),
            Column::make('state')->title('State / Province'),
            Column::make('city')->title('City'),
            Column::make('address')->title('Address'),

            // Kolom baru untuk Total Sales
            Column::make('total_sales')
                ->title('Total Sales')
                ->className('text-center align-middle'),

            // Kolom baru untuk Total Paid
            Column::make('total_paid')
                ->title('Total Paid')
                ->className('text-center align-middle'),

            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->className('text-center align-middle'),

            Column::make('created_at')->visible(false)
        ];
    }

    protected function filename(): string {
        return 'Customers_' . date('YmdHis');
    }

}
