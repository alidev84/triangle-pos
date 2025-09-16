@extends('layouts.app')

@section('title', 'Supplier Details')

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('suppliers.index') }}">Suppliers</a></li>
        <li class="breadcrumb-item active">Details</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Supplier Name</th>
                                    <td>{{ $supplier->supplier_name }}</td>
                                </tr>
                                <tr>
                                    <th>Supplier Email</th>
                                    <td>{{ $supplier->supplier_email }}</td>
                                </tr>
                                <tr>
                                    <th>Supplier Phone</th>
                                    <td>{{ $supplier->supplier_phone }}</td>
                                </tr>
                                <tr>
                                    <th>City</th>
                                    <td>{{ $supplier->city }}</td>
                                </tr>
                                <tr>
                                    <th>Country</th>
                                    <td>{{ $supplier->country }}</td>
                                </tr>
                                <tr>
                                    <th>Address</th>
                                    <td>{{ $supplier->address }}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- Payment Section -->
                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Payment Information</h5>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                    <i class="bi bi-credit-card"></i> Add Payment
                                </button>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm mb-3">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted">Total Purchases</h6>
                                            <h4 class="text-primary">{{ format_currency($supplier->total_purchases) }}</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm mb-3">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted">Total Paid</h6>
                                            <h4 class="text-success">{{ format_currency($supplier->total_paid_amount) }}</h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm mb-3">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted">Outstanding Balance</h6>
                                            <h4 class="{{ $supplier->outstanding_balance > 0 ? 'text-danger' : 'text-success' }}">
                                                {{ format_currency($supplier->outstanding_balance) }}
                                            </h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payment History -->
                            <div class="card border-0 shadow-sm mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Payment History</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Reference</th>
                                                    <th>Amount</th>
                                                    <th>Method</th>
                                                    <th>Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($supplier->payments as $payment)
                                                    <tr>
                                                        <td>{{ $payment->date->format('d M Y') }}</td>
                                                        <td>{{ $payment->reference }}</td>
                                                        <td>{{ format_currency($payment->amount) }}</td>
                                                        <td>{{ $payment->payment_method }}</td>
                                                        <td>{{ $payment->note }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" class="text-center">No payment records found</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment - {{ $supplier->supplier_name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <livewire:supplier-payment-form :supplierId="$supplier->id" />
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>
@endsection

