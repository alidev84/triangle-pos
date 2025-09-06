@extends('layouts.app')

@section('title', 'Customer Billing Details')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">{{ $customer->customer_name }} - Billing Details</h4>
                    <div>
                        <a href="{{ route('customers.billing.create', $customer->id) }}" class="btn btn-success">
                            <i class="bi bi-plus"></i> Add Bill
                        </a>
                        <a href="{{ route('customers.billing.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Customers
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Billed</h5>
                                    <h3>{{ format_currency($totalBilled) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Paid</h5>
                                    <h3>{{ format_currency($totalPaid) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-{{ $totalRemaining > 0 ? 'danger' : 'success' }} text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Remaining</h5>
                                    <h3>{{ format_currency($totalRemaining) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Bills</h5>
                                    <h3>{{ $billingReports->count() }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bills Table -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Bill Ref#</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Paid</th>
                                    <th>Remaining</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($billingReports as $bill)
                                    <tr>
                                        <td>{{ $bill->bill_reference }}</td>
                                        <td>{{ $bill->bill_date->format('d M, Y') }}</td>
                                        <td>{{ format_currency($bill->bill_amount) }}</td>
                                        <td>{{ format_currency($bill->paid_amount) }}</td>
                                        <td>{{ format_currency($bill->remaining_amount) }}</td>
                                        <td>
                                            @if($bill->payment_status == 'paid')
                                                <span class="badge badge-success">Paid</span>
                                            @elseif($bill->payment_status == 'partial')
                                                <span class="badge badge-warning">Partial</span>
                                            @else
                                                <span class="badge badge-danger">Unpaid</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('customers.billing.edit', [$customer->id, $bill->id]) }}" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                @if($bill->remaining_amount > 0)
                                                    <button type="button" class="btn btn-sm btn-success" 
                                                            data-toggle="modal" 
                                                            data-target="#paymentModal{{ $bill->id }}">
                                                        <i class="bi bi-credit-card"></i>
                                                    </button>
                                                @endif
                                                <form action="{{ route('customers.billing.destroy', [$customer->id, $bill->id]) }}" 
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this bill?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Payment Modal -->
                                    <div class="modal fade" id="paymentModal{{ $bill->id }}" tabindex="-1" role="dialog">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Add Payment</h5>
                                                    <button type="button" class="close" data-dismiss="modal">
                                                        <span>&times;</span>
                                                    </button>
                                                </div>
                                                <form action="{{ route('customers.billing.payment', [$customer->id, $bill->id]) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label>Bill Reference: {{ $bill->bill_reference }}</label>
                                                        </div>
                                                        <div class="form-group">
                                                            <label>Remaining Amount: {{ format_currency($bill->remaining_amount) }}</label>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="payment_amount">Payment Amount <span class="text-danger">*</span></label>
                                                            <input type="number" step="0.01" max="{{ $bill->remaining_amount }}" 
                                                                   class="form-control" name="payment_amount" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="payment_date">Payment Date <span class="text-danger">*</span></label>
                                                            <input type="date" class="form-control" name="payment_date" 
                                                                   value="{{ date('Y-m-d') }}" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-success">Add Payment</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No bills found for this customer</td>
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
@endsection