@extends('layouts.app')

@section('title', 'Customer Billing Reports')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Wholesale Customer Billing Reports</h4>
                    <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Customers
                    </a>
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

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Customer Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Type</th>
                                    <th>Total Bills</th>
                                    <th>Total Billed</th>
                                    <th>Total Paid</th>
                                    <th>Remaining</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customers as $customer)
                                    <tr>
                                        <td>{{ $customer->customer_name }}</td>
                                        <td>{{ $customer->customer_email }}</td>
                                        <td>{{ $customer->customer_phone }}</td>
                                        <td>
                                            <span class="badge badge-{{ $customer->customer_type == 'wholesale' ? 'primary' : 'secondary' }}">
                                                {{ ucfirst($customer->customer_type) }}
                                            </span>
                                        </td>
                                        <td>{{ $customer->billingReports->count() }}</td>
                                        <td>{{ format_currency($customer->billingReports->sum('bill_amount')) }}</td>
                                        <td>{{ format_currency($customer->billingReports->sum('paid_amount')) }}</td>
                                        <td>
                                            <span class="badge badge-{{ $customer->billingReports->sum('remaining_amount') > 0 ? 'danger' : 'success' }}">
                                                {{ format_currency($customer->billingReports->sum('remaining_amount')) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('customers.billing.show', $customer->id) }}" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> View Bills
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No wholesale customers found</td>
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