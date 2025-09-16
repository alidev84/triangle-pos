<div class="modal fade" id="checkoutModal" tabindex="-1" role="dialog" aria-labelledby="checkoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="checkoutModalLabel">
                    <i class="bi bi-cart-check text-primary"></i> Confirm Sale
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="checkout-form" action="{{ route('app.pos.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    @if (session()->has('checkout_message'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <div class="alert-body">
                                <span>{{ session('checkout_message') }}</span>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">×</span>
                                </button>
                            </div>
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-lg-7">
                            <input type="hidden" value="{{ $customer_id }}" name="customer_id">
                            <input type="hidden" value="{{ $global_tax }}" name="tax_percentage">
                            <input type="hidden" value="{{ $global_discount }}" name="discount_percentage">
                            <input type="hidden" value="{{ $shipping }}" name="shipping_amount">
                            <div class="form-row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="total_amount">Total Amount <span class="text-danger">*</span></label>
                                        @php
                                            $grand_total = Cart::instance($cart_instance)->total() + (float) $shipping;
                                        @endphp
                                        <input id="total_amount" type="text" class="form-control" name="total_amount" value="{{ $grand_total }}" readonly required>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="paid_amount">Received Amount <span class="text-danger">*</span></label>
                                        @php
                                            $grand_total = Cart::instance($cart_instance)->total() + (float) $shipping;
                                        @endphp
                                        <input id="paid_amount" type="text" class="form-control" name="paid_amount" value="{{ $grand_total }}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="payment_method">Payment Method <span class="text-danger">*</span></label>
                                        <select class="form-control" name="payment_method" id="payment_method" required onchange="handlePosPaymentMethodChange()">
                                            <option value="Cash">Cash</option>
                                            <option value="On Credit">On Credit</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Mobile Wallet Transfer">Mobile Wallet Transfer</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="change_amount">Change to Give</label>
                                        <input id="change_amount" type="text" class="form-control" readonly style="background-color: #e9ecef; font-weight: bold; color: #28a745;">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="note">Note (If Needed)</label>
                                <textarea name="note" id="note" rows="3" class="form-control"></textarea>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <tr>
                                        <th>Total Products</th>
                                        <td>
                                                <span class="badge badge-success">
                                                    {{ Cart::instance($cart_instance)->count() }}
                                                </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Order Tax ({{ $global_tax }}%)</th>
                                        <td>(+) {{ format_currency(Cart::instance($cart_instance)->tax()) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Discount ({{ $global_discount }}%)</th>
                                        <td>(-) {{ format_currency(Cart::instance($cart_instance)->discount()) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Shipping</th>
                                        <input type="hidden" value="{{ $shipping }}" name="shipping_amount">
                                        <td>(+) {{ format_currency($shipping) }}</td>
                                    </tr>
                                    <tr class="text-primary">
                                        <th>Grand Total</th>
                                        @php
                                            $total_with_shipping = Cart::instance($cart_instance)->total() + (float) $shipping
                                        @endphp
                                        <th>
                                            (=) {{ format_currency($total_with_shipping) }}
                                        </th>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function handlePosPaymentMethodChange() {
    const paymentMethod = document.getElementById('payment_method').value;
    const paidAmountInput = document.getElementById('paid_amount');
    const totalAmount = parseFloat(document.getElementById('total_amount').value) || 0;
    
    if (paymentMethod === 'On Credit') {
        paidAmountInput.value = '0';
        paidAmountInput.readOnly = true;
        paidAmountInput.style.backgroundColor = '#f8f9fa';
        paidAmountInput.placeholder = 'Amount set to 0 for credit payments';
        // Remove required attribute for credit payments
        paidAmountInput.removeAttribute('required');
    } else {
        paidAmountInput.readOnly = false;
        paidAmountInput.style.backgroundColor = '';
        paidAmountInput.placeholder = 'Enter received amount';
        paidAmountInput.setAttribute('required', 'required');
        // Reset to total amount if not credit
        paidAmountInput.value = totalAmount;
    }
    calculateChange();
}

function calculateChange() {
    const totalAmount = parseFloat(document.getElementById('total_amount').value) || 0;
    const receivedAmount = parseFloat(document.getElementById('paid_amount').value) || 0;
    const changeAmount = receivedAmount - totalAmount;
    const changeInput = document.getElementById('change_amount');
    
    if (changeAmount >= 0) {
        changeInput.value = changeAmount.toFixed(2);
        changeInput.style.color = '#28a745'; // Green for positive change
    } else {
        changeInput.value = Math.abs(changeAmount).toFixed(2) + ' (Due)';
        changeInput.style.color = '#dc3545'; // Red for amount due
    }
}

// Function to update modal totals when cart changes
function updateModalTotals() {
    // Find the Grand Total from the right panel
    let grandTotal = 0;
    
    // Look for "Grand Total" text followed by amount
    const grandTotalElements = document.querySelectorAll('*');
    for (let element of grandTotalElements) {
        const text = element.textContent.trim();
        if (text.includes('Grand Total') && text.includes('=')) {
            // Extract the amount after the equals sign
            const match = text.match(/=\s*\$?([0-9,]+\.?[0-9]*)/);
            if (match) {
                grandTotal = parseFloat(match[1].replace(/,/g, ''));
                break;
            }
        }
    }
    
    // Alternative: Look for elements with "Grand Total" and nearby amount
    if (grandTotal === 0) {
        const grandTotalLabels = Array.from(document.querySelectorAll('*')).filter(el => 
            el.textContent.trim() === 'Grand Total'
        );
        
        for (let label of grandTotalLabels) {
            // Look for sibling or parent elements with amount
            const parent = label.closest('tr, div, .row');
            if (parent) {
                const amountElements = parent.querySelectorAll('*');
                for (let amountEl of amountElements) {
                    const amountText = amountEl.textContent.trim();
                    if (amountText.includes('$') && amountText.match(/\$([0-9,]+\.?[0-9]*)/)) {
                        const match = amountText.match(/\$([0-9,]+\.?[0-9]*)/);
                        if (match) {
                            grandTotal = parseFloat(match[1].replace(/,/g, ''));
                            break;
                        }
                    }
                }
                if (grandTotal > 0) break;
            }
        }
    }
    
    // Update modal fields if we found a total
    if (grandTotal > 0) {
        const totalAmountInput = document.getElementById('total_amount');
        const paidAmountInput = document.getElementById('paid_amount');
        
        if (totalAmountInput && paidAmountInput) {
            totalAmountInput.value = grandTotal.toFixed(2);
            
            // Update paid amount to match total (unless it's credit payment)
            const paymentMethod = document.getElementById('payment_method');
            if (paymentMethod && paymentMethod.value !== 'On Credit') {
                paidAmountInput.value = grandTotal.toFixed(2);
            }
            
            // Recalculate change
            calculateChange();
        }
    }
}

// Add event listener for paid amount changes
document.addEventListener('DOMContentLoaded', function() {
    const paidAmountInput = document.getElementById('paid_amount');
    const paymentMethodSelect = document.getElementById('payment_method');
    
    if (paidAmountInput) {
        paidAmountInput.addEventListener('input', calculateChange);
        paidAmountInput.addEventListener('keyup', calculateChange);
        paidAmountInput.addEventListener('change', calculateChange);
    }
    
    if (paymentMethodSelect) {
        paymentMethodSelect.addEventListener('change', function() {
            updateModalTotals();
            calculateChange();
        });
    }
    
    // Listen for cart updates (Livewire events)
    window.addEventListener('cartUpdated', function() {
        setTimeout(updateModalTotals, 100); // Small delay to ensure DOM is updated
    });
    
    // Also check for changes when modal is shown
    $('#checkoutModal').on('shown.bs.modal', function() {
        setTimeout(function() {
            updateModalTotals();
            calculateChange();
        }, 200);
    });
    
    // Modify form submission to handle credit payments
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            const paymentMethod = document.getElementById('payment_method').value;
            const paidAmount = document.getElementById('paid_amount').value;
            
            // Allow submission for credit payments even with 0 amount
            if (paymentMethod === 'On Credit' && (paidAmount === '0' || paidAmount === '')) {
                // Set paid amount to 0 for credit payments
                document.getElementById('paid_amount').value = '0';
                return true;
            }
            
            // For other payment methods, ensure paid amount is provided
            if (paymentMethod !== 'On Credit' && (!paidAmount || parseFloat(paidAmount) <= 0)) {
                e.preventDefault();
                alert('Please enter a valid received amount.');
                return false;
            }
            
            return true;
        });
    }
});
</script>
