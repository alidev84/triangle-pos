@extends('layouts.app')

@section('title', 'Supplier Billing Reports')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Supplier Billing Reports</h4>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Suppliers
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
                                    <th>Supplier Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Total Bills</th>
                                    <th>Total Billed</th>
                                    <th>Total Paid</th>
                                    <th>Remaining</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($suppliers as $supplier)
                                    <tr>
                                        <td>{{ $supplier->supplier_name }}</td>
                                        <td>{{ $supplier->supplier_email }}</td>
                                        <td>{{ $supplier->supplier_phone }}</td>
                                        <td>{{ $supplier->billingReports->count() }}</td>
                                        <td>{{ format_currency($supplier->getTotalBilledAmount()) }}</td>
                                        <td>{{ format_currency($supplier->getTotalPaidAmount()) }}</td>
                                        <td>
                                            <span class="badge badge-{{ $supplier->getTotalRemainingAmount() > 0 ? 'danger' : 'success' }}">
                                                {{ format_currency($supplier->getTotalRemainingAmount()) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('suppliers.billing.show', $supplier->id) }}" class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> View Bills
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No suppliers found</td>
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