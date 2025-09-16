@extends('layouts.app')

@section('title', 'Sales')

@section('third_party_stylesheets')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap4.min.css">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item active">Sales</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <a href="{{ route('sales.create') }}" class="btn btn-primary">
                                    Add Sale <i class="bi bi-plus"></i>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="start_date">Start Date:</label>
                                        <input type="date" id="start_date" class="form-control" value="{{ date('Y-m-d') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="end_date">End Date:</label>
                                        <input type="date" id="end_date" class="form-control" value="{{ date('Y-m-d') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="table-responsive">
                            {!! $dataTable->table() !!}
                        </div>

                        <!-- Daily Totals Summary -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-header">
                                        <h5 class="mb-0">Daily Summary</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row text-center">
                                            <div class="col-md-3">
                                                <h6>Total Sales Amount</h6>
                                                <h4 class="text-primary" id="total-sales-amount">{{ format_currency(0) }}</h4>
                                            </div>
                                            <div class="col-md-3">
                                                <h6>Total Paid Amount</h6>
                                                <h4 class="text-success" id="total-paid-amount">{{ format_currency(0) }}</h4>
                                            </div>
                                            <div class="col-md-3">
                                                <h6>Total Due Amount</h6>
                                                <h4 class="text-danger" id="total-due-amount">{{ format_currency(0) }}</h4>
                                            </div>
                                            <div class="col-md-3">
                                                <h6>Total Change Given</h6>
                                                <h4 class="text-info" id="total-change-amount">{{ format_currency(0) }}</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('page_scripts')
    {!! $dataTable->scripts() !!}
    
    <script>
        $(document).ready(function() {
            var table = $('#sales-table').DataTable();
            
            // Function to calculate and update totals
            function updateTotals() {
                var totalSales = 0;
                var totalPaid = 0;
                var totalDue = 0;
                var totalChange = 0;
                
                table.rows({search: 'applied'}).data().each(function(row) {
                    // Extract numeric values from formatted currency strings
                    var salesAmount = parseFloat(row.total_amount.replace(/[^0-9.-]+/g, '')) || 0;
                    var paidAmount = parseFloat(row.paid_amount.replace(/[^0-9.-]+/g, '')) || 0;
                    var dueAmount = parseFloat(row.due_amount.replace(/[^0-9.-]+/g, '')) || 0;
                    var changeAmount = parseFloat(row.change_amount.replace(/[^0-9.-]+/g, '')) || 0;
                    
                    totalSales += salesAmount;
                    totalPaid += paidAmount;
                    totalDue += dueAmount;
                    totalChange += changeAmount;
                });
                
                // Update the summary cards
                $('#total-sales-amount').text('$' + totalSales.toFixed(2));
                $('#total-paid-amount').text('$' + totalPaid.toFixed(2));
                $('#total-due-amount').text('$' + totalDue.toFixed(2));
                $('#total-change-amount').text('$' + totalChange.toFixed(2));
            }
            
            // Update totals when table is drawn
            table.on('draw', function() {
                updateTotals();
            });
            
            // Date filter functionality
            $('#start_date, #end_date').on('change', function() {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();
                
                if (startDate && endDate) {
                    // Reload DataTable with date filter
                    table.ajax.url('{{ route('sales.index') }}?start_date=' + startDate + '&end_date=' + endDate).load();
                }
            });
            
            // Initial totals calculation
            updateTotals();
        });
    </script>
@endpush
