@extends('layouts.app')

@section('title', 'Edit Bill')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Edit Bill - {{ $supplier->supplier_name }}</h4>
                    <a href="{{ route('suppliers.billing.show', $supplier->id) }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('suppliers.billing.update', [$supplier->id, $billingReport->id]) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bill_reference">Bill Reference <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('bill_reference') is-invalid @enderror" 
                                           name="bill_reference" value="{{ old('bill_reference', $billingReport->bill_reference) }}" required>
                                    @error('bill_reference')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bill_date">Bill Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('bill_date') is-invalid @enderror" 
                                           name="bill_date" value="{{ old('bill_date', $billingReport->bill_date->format('Y-m-d')) }}" required>
                                    @error('bill_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bill_amount">Bill Amount <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('bill_amount') is-invalid @enderror" 
                                           name="bill_amount" value="{{ old('bill_amount', $billingReport->bill_amount) }}" required>
                                    @error('bill_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="paid_amount">Paid Amount <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('paid_amount') is-invalid @enderror" 
                                           name="paid_amount" value="{{ old('paid_amount', $billingReport->paid_amount) }}" required>
                                    @error('paid_amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                              name="notes" rows="3">{{ old('notes', $billingReport->notes) }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check"></i> Update Bill
                            </button>
                            <a href="{{ route('suppliers.billing.show', $supplier->id) }}" class="btn btn-secondary">
                                <i class="bi bi-x"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection