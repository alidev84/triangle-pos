<div>
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form wire:submit="generateReport">
                        <div class="form-row">
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label>Start Date <span class="text-danger">*</span></label>
                                    <input wire:model="start_date" type="date" class="form-control" name="start_date">
                                    @error('start_date')
                                    <span class="text-danger mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label>End Date <span class="text-danger">*</span></label>
                                    <input wire:model="end_date" type="date" class="form-control" name="end_date">
                                    @error('end_date')
                                    <span class="text-danger mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label>Supplier</label>
                                    <select wire:model="supplier_id" class="form-control" name="supplier_id">
                                        <option value="">Select Supplier</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}">{{ $supplier->supplier_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label>Payment Status</label>
                                    <select wire:model="payment_status" class="form-control" name="payment_status">
                                        <option value="">Select Payment Status</option>
                                        <option value="Paid">Paid</option>
                                        <option value="Unpaid">Unpaid</option>
                                        <option value="Partial">Partial</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary">
                                <span wire:target="generateReport" wire:loading class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                <i wire:target="generateReport" wire:loading.remove class="bi bi-shuffle"></i>
                                Filter Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($supplier_id)
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">
                            @if($selected_supplier)
                                {{ $selected_supplier->supplier_name }} - Purchase Bills
                                @if($this->is_account_settled)
                                    <span class="badge bg-success">Settled</span>
                                @elseif($this->is_account_ever_settled)
                                    <span class="badge bg-warning ms-2">New Purchases</span>
                                @endif
                            @endif
                        </h5>
                        @if($selected_supplier && $this->is_account_ever_settled)
                            <small class="text-muted">
                                Account was settled on {{ \Carbon\Carbon::parse($selected_supplier->payments()->latest('payment_date')->first()->payment_date)->format('M d, Y') }}
                            </small>
                        @endif
                    </div>
                    <button wire:click="showPaymentModal" class="btn btn-success btn-sm" @if($this->is_account_settled) disabled @endif>
                        <i class="bi bi-plus-circle"></i> Record Payment
                        @if($is_account_settled)
                            <span class="ms-1" data-bs-toggle="tooltip" title="Account is already settled">
                                <i class="bi bi-lock"></i>
                            </span>
                        @endif
                    </button>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped text-center mb-0">
                        <div wire:loading.flex class="col-12 position-absolute justify-content-center align-items-center" style="top:0;right:0;left:0;bottom:0;background-color: rgba(255,255,255,0.5);z-index: 99;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Due Amount</th>
                            <th>Payment Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($purchases as $purchase)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($purchase->date)->format('d M, Y') }}</td>
                                <td>{{ $purchase->reference }}</td>
                                <td>{{ format_currency($purchase->total_amount) }}</td>
                                <td>{{ format_currency($purchase->paid_amount) }}</td>
                                <td>{{ format_currency($purchase->due_amount) }}</td>
                                <td>
                                    @if ($purchase->payment_status == 'Partial')
                                        <span class="badge badge-warning">
                                    {{ $purchase->payment_status }}
                                </span>
                                    @elseif ($purchase->payment_status == 'Paid')
                                        <span class="badge badge-success">
                                    {{ $purchase->payment_status }}
                                </span>
                                    @else
                                        <span class="badge badge-danger">
                                    {{ $purchase->payment_status }}
                                </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <span class="text-danger">No Purchase Data Available!</span>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div @class(['mt-3' => $purchases->hasPages()])>
                        {{ $purchases->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment History -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Payment History</h5>
                </div>
                <div class="card-body">
                    @if($supplier && $supplier->payments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped text-center mb-0">
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($supplier->payments as $payment)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M, Y') }}</td>
                                        <td>{{ format_currency($payment->amount) }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
                                        </td>
                                        <td>{{ $payment->notes ?: 'N/A' }}</td>
                                        <td>
                                            <button wire:click="editPayment({{ $payment->id }})" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button wire:click="deletePayment({{ $payment->id }})" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">No payment history available.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Card -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Purchase Amount</h5>
                                    <h3>{{ format_currency($total_purchases) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Paid</h5>
                                    <h3>{{ format_currency($total_paid) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Remaining</h5>
                                    <h3>{{ format_currency($total_remaining) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Payment Modal -->
    @if($show_payment_modal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="hidePaymentModal"></button>
                </div>
                <form wire:submit.prevent="recordPayment">
                    <div class="modal-body">
                        @if($is_account_ever_settled)
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                This account was previously settled. Only new purchases will be affected by this payment.
                            </div>
                        @endif
                        <div class="form-group mb-3">
                            <label for="payment_amount">Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input 
                                    wire:model.live.debounce.300ms="payment_amount" 
                                    type="text" 
                                    inputmode="decimal"
                                    class="form-control @error('payment_amount') is-invalid @enderror" 
                                    placeholder="0.00" 
                                    id="supplier_payment_amount_input"
                                    min="0.01" 
                                    max="{{ $remaining_balance }}"
                                    oninput="formatAmount(this)"
                                    onblur="formatAmountOnBlur(this)"
                                    @if($is_account_settled) disabled @endif
                                    required>
                            </div>
                            @error('payment_amount') 
                                <span class="text-danger">{{ $message }}</span> 
                            @enderror
                            <small class="text-muted">
                                Remaining balance: {{ format_currency($remaining_balance) }}
                                @if($is_account_settled)
                                    <span class="text-danger">(Account is settled)</span>
                                @endif
                            </small>
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="payment_date">Date <span class="text-danger">*</span></label>
                            <input type="date" 
                                   class="form-control" 
                                   id="payment_date" 
                                   wire:model="payment_date" 
                                   @if($is_account_settled) disabled @endif
                                   required>
                            @error('payment_date') 
                                <span class="text-danger">{{ $message }}</span> 
                            @enderror
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="payment_method">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-control" 
                                    id="payment_method" 
                                    wire:model="payment_method" 
                                    @if($this->is_account_settled) disabled @endif
                                    onchange="handleSupplierPaymentMethodChange()"
                                    required>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="check">Check</option>
                                <option value="on_credit">On Credit</option>
                            </select>
                            @error('payment_method') 
                                <span class="text-danger">{{ $message }}</span> 
                            @enderror
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="payment_notes">Notes</label>
                            <textarea class="form-control" 
                                      id="payment_notes" 
                                      wire:model="payment_notes" 
                                      rows="3"
                                      @if($is_account_settled) disabled @endif>
                            </textarea>
                            @error('payment_notes') 
                                <span class="text-danger">{{ $message }}</span> 
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" 
                                class="btn btn-secondary" 
                                data-bs-dismiss="modal" 
                                wire:click="hidePaymentModal"
                                @if($is_processing) disabled @endif>
                            Cancel
                        </button>
                        <button type="submit" 
                                class="btn btn-primary"
                                @if($is_account_settled || $is_processing) disabled @endif>
                            <span wire:loading wire:target="recordPayment" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            {{ $is_processing ? 'Processing...' : 'Save Payment' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

    <!-- Edit Payment Modal -->
    @if($show_edit_payment_modal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Payment</h5>
                    <button type="button" class="close" wire:click="hideEditPaymentModal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form wire:submit="updatePayment">
                        <div class="form-group">
                            <label>Payment Amount <span class="text-danger">*</span></label>
                            <input wire:model="editing_payment_amount" type="number" step="0.01" class="form-control" placeholder="Enter payment amount" id="edit_payment_amount_input">
                            @error('editing_payment_amount')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Payment Date <span class="text-danger">*</span></label>
                            <input wire:model="editing_payment_date" type="date" class="form-control">
                            @error('editing_payment_date')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Payment Method <span class="text-danger">*</span></label>
                            <select wire:model="editing_payment_method" class="form-control" id="edit_payment_method_select" onchange="handleEditPaymentMethodChange()">
                                <option value="cash">Cash</option>
                                <option value="on_credit">On Credit</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="mobile_wallet">Mobile Wallet Transfer</option>
                            </select>
                            @error('editing_payment_method')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea wire:model="editing_payment_notes" class="form-control" rows="3" placeholder="Enter payment notes (optional)"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="hideEditPaymentModal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <span wire:target="updatePayment" wire:loading class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Update Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif
</div>

<script>
function formatAmount(input) {
    // Get cursor position
    const cursorPosition = input.selectionStart;
    
    // Remove any non-numeric characters except decimal point
    let value = input.value.replace(/[^\d.]/g, '');
    
    // Ensure only one decimal point
    const decimalSplit = value.split('.');
    if (decimalSplit.length > 2) {
        value = decimalSplit[0] + '.' + decimalSplit.slice(1).join('');
    }
    
    // Update the input value
    input.value = value;
    
    // Restore cursor position
    setTimeout(() => {
        input.selectionStart = cursorPosition;
        input.selectionEnd = cursorPosition;
    }, 0);
    
    // Update Livewire model
    const event = new Event('input', { bubbles: true });
    input.dispatchEvent(event);
}

function formatAmountOnBlur(input) {
    // Parse the value as a float
    let value = parseFloat(input.value);
    
    // If it's a valid number, format it with 2 decimal places
    if (!isNaN(value)) {
        // Ensure value is within min/max range
        const min = parseFloat(input.min) || 0.01;
        const max = parseFloat(input.max) || Number.MAX_SAFE_INTEGER;
        value = Math.min(Math.max(value, min), max);
        
        // Format with 2 decimal places
        input.value = value.toFixed(2);
        
        // Update Livewire model
        const event = new Event('input', { bubbles: true });
        input.dispatchEvent(event);
    } else if (input.value.trim() !== '') {
        // If invalid input but not empty, set to minimum value
        input.value = (parseFloat(input.min) || 0.01).toFixed(2);
        const event = new Event('input', { bubbles: true });
        input.dispatchEvent(event);
    }
}

function handleSupplierPaymentMethodChange() {
    const paymentMethod = document.getElementById('payment_method').value;
    const checkNumberField = document.getElementById('check_number_field');
    const referenceNumberField = document.getElementById('reference_number_field');
    
    if (paymentMethod === 'check') {
        checkNumberField.style.display = 'block';
        referenceNumberField.style.display = 'none';
    } else if (paymentMethod === 'bank_transfer' || paymentMethod === 'other') {
        checkNumberField.style.display = 'none';
        referenceNumberField.style.display = 'block';
    } else {
        checkNumberField.style.display = 'none';
        referenceNumberField.style.display = 'none';
    }
}

document.addEventListener('livewire:initialized', () => {
    // Set today's date as default for payment date
    const today = new Date().toISOString().split('T')[0];
    @this.set('payment_date', today);
    
    // Initialize amount field formatting
    const amountInput = document.getElementById('supplier_payment_amount_input');
    if (amountInput) {
        // Add event listeners for amount formatting
        amountInput.addEventListener('input', function() {
            formatAmount(this);
        });
        
        amountInput.addEventListener('blur', function() {
            formatAmountOnBlur(this);
        });
        
        // Initialize with empty value
        amountInput.value = '';
    }
    
    // Initialize payment method fields
    const paymentMethodSelect = document.getElementById('payment_method');
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', handleSupplierPaymentMethodChange);
        // Initialize on page load
        handleSupplierPaymentMethodChange();
    }
});
</script>
