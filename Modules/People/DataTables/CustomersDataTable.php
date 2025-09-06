<?php

namespace Modules\People\DataTables;


use Modules\People\Entities\Customer;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class CustomersDataTable extends DataTable
{

    public function dataTable($query) {
        return datatables()
            ->eloquent($query)
            ->addColumn('customer_type', function ($data) {
                $badgeClass = $data->customer_type == 'wholesale' ? 'badge-primary' : 'badge-secondary';
                return '<span class="badge ' . $badgeClass . '">' . ucfirst($data->customer_type) . '</span>';
            })
            ->addColumn('billing_action', function ($data) {
                if ($data->customer_type == 'wholesale') {
                    return '<a href="' . route('customers.billing.show', $data->id) . '" class="btn btn-sm btn-info">
                                <i class="bi bi-receipt"></i> Billing
                            </a>';
                }
                return '-';
            })
            ->addColumn('action', function ($data) {
                return view('people::customers.partials.actions', compact('data'));
            })
            ->rawColumns(['customer_type', 'billing_action', 'action']);
    }

    public function query(Customer $model) {
        return $model->newQuery();
    }

    public function html() {
        return $this->builder()
            ->setTableId('customers-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom("<'row'<'col-md-3'l><'col-md-5 mb-2'B><'col-md-4'f>> .
                                       'tr' .
                                 <'row'<'col-md-5'i><'col-md-7 mt-2'p>>")
            ->orderBy(4)
            ->buttons(
                Button::make('excel')
                    ->text('<i class="bi bi-file-earmark-excel-fill"></i> Excel'),
                Button::make('print')
                    ->text('<i class="bi bi-printer-fill"></i> Print'),
                Button::make('reset')
                    ->text('<i class="bi bi-x-circle"></i> Reset'),
                Button::make('reload')
                    ->text('<i class="bi bi-arrow-repeat"></i> Reload')
            );
    }

    protected function getColumns() {
        return [
            Column::make('customer_name')
                ->className('text-center align-middle'),

            Column::make('customer_email')
                ->className('text-center align-middle'),

            Column::make('customer_phone')
                ->className('text-center align-middle'),

            Column::computed('customer_type')
                ->title('Type')
                ->exportable(false)
                ->printable(false)
                ->className('text-center align-middle'),

            Column::computed('billing_action')
                ->title('Billing')
                ->exportable(false)
                ->printable(false)
                ->className('text-center align-middle'),

            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->className('text-center align-middle'),

            Column::make('created_at')
                ->visible(false)
        ];
    }

    protected function filename(): string {
        return 'Customers_' . date('YmdHis');
    }
}
