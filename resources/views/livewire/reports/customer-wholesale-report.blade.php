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
                                    <label>Customer Type</label>
                                    <select wire:model="customer_type" class="form-control" name="customer_type">
                                        <option value="wholesale">Wholesale Buyers</option>
                                        <option value="retail">Retail Buyers</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="form-group">
                                    <label>Customer</label>
                                    <select wire:model="customer_id" class="form-control" name="customer_id">
                                        <option value="">Select Customer</option>
                                        @foreach($filtered_customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->customer_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="col-lg-6">
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
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <span wire:target="generateReport" wire:loading class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                            <i wire:target="generateReport" wire:loading.remove class="bi bi-shuffle"></i>
                                            Filter Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($customer_id)
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        @if($selected_customer)
                            {{ $selected_customer->customer_name }} - Sales Bills
                            <small class="text-muted">({{ ucfirst($selected_customer->customer_type) }} Customer)</small>
                        @endif
                    </h5>
                    <button wire:click="showPaymentModal" class="btn btn-success btn-sm" @if($this->is_account_settled) disabled @endif>
                        <i class="bi bi-plus-circle"></i> Record Payment
                        @if($this->is_account_settled)
                            <span class="badge bg-secondary ml-2">Account Settled</span>
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
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($sales as $sale)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($sale->date)->format('d M, Y') }}</td>
                                <td>{{ $sale->reference }}</td>
                                <td>{{ format_currency($sale->total_amount) }}</td>
                                <td>{{ format_currency($sale->paid_amount) }}</td>
                                <td>{{ format_currency($sale->due_amount) }}</td>
                                <td>
                                    @if ($sale->payment_status == 'Partial')
                                        <span class="badge badge-warning">
                                            {{ $sale->payment_status }}
                                        </span>
                                    @elseif ($sale->payment_status == 'Paid')
                                        <span class="badge badge-success">
                                            {{ $sale->payment_status }}
                                        </span>
                                    @else
                                        <span class="badge badge-danger">
                                            {{ $sale->payment_status }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <span class="text-danger">No Sales Data Available!</span>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    <div @class(['mt-3' => $sales->hasPages()])>
                        {{ $sales->links() }}
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
                    @if($customer && $customer->payments->count() > 0)
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
                                @foreach($customer->payments as $payment)
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
                                    <h5 class="card-title">Total Sales Amount</h5>
                                    <h3>{{ format_currency($total_sales) }}</h3>
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
                    <button type="button" class="close" wire:click="hidePaymentModal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="recordPayment">
                        <div class="form-group">
                            <label>Payment Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input 
                                    wire:model.live.debounce.300ms="payment_amount" 
                                    type="number" 
                                    step="0.01" 
                                    class="form-control @error('payment_amount') is-invalid @enderror" 
                                    placeholder="Enter payment amount" 
                                    id="customer_payment_amount_input"
                                    @if($this->is_account_settled) disabled @endif
                                >
                            </div>
                            @error('payment_amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            @if(!$this->is_account_settled)
                                <small class="form-text text-muted">
                                    Remaining Balance: {{ format_currency($this->remaining_balance) }}
                                </small>
                            @endif
                        </div>
                        <div class="form-group">
                            <label>Payment Date <span class="text-danger">*</span></label>
                            <input wire:model="payment_date" type="date" class="form-control">
                            @error('payment_date')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Payment Method <span class="text-danger">*</span></label>
                            <select wire:model="payment_method" class="form-control" id="customer_payment_method_select" onchange="handleCustomerPaymentMethodChange()">
                                <option value="cash">Cash</option>
                                <option value="on_credit">On Credit</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="mobile_wallet">Mobile Wallet Transfer</option>
                            </select>
                            @error('payment_method')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea wire:model="payment_notes" class="form-control" rows="3" placeholder="Enter payment notes (optional)"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="hidePaymentModal" @if($isProcessing) disabled @endif>Cancel</button>
                            <button type="submit" class="btn btn-primary" @if($this->is_account_settled || $isProcessing) disabled @endif>
                                <span wire:target="recordPayment" wire:loading.class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                <span wire:loading.remove>Record Payment</span>
                                <span wire:loading>Processing...</span>
                            </button>
                        </div>
                    </form>
                </div>
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
                            <input wire:model="editing_payment_amount" type="number" step="0.01" class="form-control" placeholder="Enter payment amount" id="edit_customer_payment_amount_input">
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
                            <select wire:model="editing_payment_method" class="form-control" id="edit_customer_payment_method_select" onchange="handleEditCustomerPaymentMethodChange()">
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
function handleCustomerPaymentMethodChange() {
    const paymentMethod = document.getElementById('customer_payment_method_select').value;
    const paymentAmountInput = document.getElementById('customer_payment_amount_input');
    
    if (paymentMethod === 'on_credit') {
        paymentAmountInput.value = '0';
        paymentAmountInput.disabled = true;
        paymentAmountInput.placeholder = 'Amount set to 0 for credit payments';
    } else {
        paymentAmountInput.disabled = false;
        paymentAmountInput.placeholder = 'Enter payment amount';
    }
}

function handleEditCustomerPaymentMethodChange() {
    const paymentMethod = document.getElementById('edit_customer_payment_method_select').value;
    const paymentAmountInput = document.getElementById('edit_customer_payment_amount_input');
    
    if (paymentMethod === 'on_credit') {
        paymentAmountInput.value = '0';
        paymentAmountInput.disabled = true;
        paymentAmountInput.placeholder = 'Amount set to 0 for credit payments';
    } else {
        paymentAmountInput.disabled = false;
        paymentAmountInput.placeholder = 'Enter payment amount';
    }
}
</script>
