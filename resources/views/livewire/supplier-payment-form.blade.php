<div class="card">
    <div class="card-header">
        <h5 class="card-title">{{ __('Record Supplier Payment') }}</h5>
    </div>
    <div class="card-body">
        @if (session()->has('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->has('payment'))
            <div class="alert alert-danger">
                {{ $errors->first('payment') }}
            </div>
        @endif

        <form wire:submit.prevent="savePayment">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="paymentDate">{{ __('Payment Date') }} <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('paymentDate') is-invalid @enderror" 
                               id="paymentDate" wire:model="paymentDate" required>
                        @error('paymentDate') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="paymentMethod">{{ __('Payment Method') }} <span class="text-danger">*</span></label>
                        <select class="form-control @error('paymentMethod') is-invalid @enderror" 
                                id="paymentMethod" wire:model="paymentMethod" required>
                            @foreach($availablePaymentMethods as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('paymentMethod') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="reference">{{ __('Reference') }}</label>
                        <input type="text" class="form-control @error('reference') is-invalid @enderror" 
                               id="reference" wire:model="reference" placeholder="e.g., Check #1234">
                        @error('reference') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="amount">{{ __('Amount') }} <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">{{ setting('default_currency_symbol') }}</span>
                            </div>
                            <input type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror" 
                                   id="amount" wire:model.live="amount" required min="0.01">
                        </div>
                        @error('amount') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="note">{{ __('Notes') }}</label>
                <textarea class="form-control @error('note') is-invalid @enderror" 
                          id="note" wire:model="note" rows="2" placeholder="Add any additional notes here"></textarea>
                @error('note') <span class="text-danger">{{ $message }}</span> @enderror
            </div>

            @if(count($allocations) > 0)
                <div class="mt-4">
                    <h6 class="mb-3">{{ __('Allocate Payment to Purchases') }}</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>{{ __('Purchase #') }}</th>
                                    <th class="text-end">{{ __('Total Amount') }}</th>
                                    <th class="text-end">{{ __('Paid Amount') }}</th>
                                    <th class="text-end">{{ __('Due Amount') }}</th>
                                    <th class="text-end">{{ __('Payment') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allocations as $index => $allocation)
                                    <tr>
                                        <td>#{{ $allocation['reference'] }}</td>
                                        <td class="text-end">{{ format_currency($allocation['total_amount']) }}</td>
                                        <td class="text-end">{{ format_currency($allocation['paid_amount']) }}</td>
                                        <td class="text-end">{{ format_currency($allocation['due_amount']) }}</td>
                                        <td>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">{{ setting('default_currency_symbol') }}</span>
                                                </div>
                                                <input type="number" 
                                                       step="0.01" 
                                                       class="form-control text-end" 
                                                       wire:model.live="allocations.{{ $index }}.amount" 
                                                       min="0" 
                                                       max="{{ $allocation['due_amount'] }}"
                                                       @if($allocation['due_amount'] <= 0) disabled @endif>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="table-active">
                                    <td colspan="3" class="text-end fw-bold">{{ __('Total Allocated:') }}</td>
                                    <td class="text-end fw-bold">{{ format_currency($totalAllocated) }}</td>
                                    <td class="text-end fw-bold">
                                        @if($remainingAmount != 0)
                                            <span class="{{ $remainingAmount > 0 ? 'text-danger' : 'text-warning' }}">
                                                {{ $remainingAmount > 0 ? 'Under-allocated' : 'Over-allocated' }}: 
                                                {{ format_currency(abs($remainingAmount)) }}
                                            </span>
                                        @else
                                            <span class="text-success">Fully allocated</span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="alert alert-info mt-4">
                    {{ __('No unpaid purchases found for this supplier.') }}
                </div>
            @endif

            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> {{ __('Cancel') }}
                </button>
                <button type="submit" class="btn btn-primary" 
                        @if(count($allocations) === 0) disabled @endif>
                    <i class="bi bi-credit-card"></i> {{ __('Record Payment') }}
                </button>
            </div>
        </form>
    </div>
</div>

@push('page_scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        // Auto-format currency inputs on blur
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('blur', function(e) {
                if (this.value) {
                    this.value = parseFloat(this.value).toFixed(2);
                }
            });
        });
    });
</script>
@endpush
