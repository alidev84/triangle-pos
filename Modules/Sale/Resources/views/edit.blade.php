@extends('layouts.app')

@section('title', 'Edit Sale')

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
        <li class="breadcrumb-item active">Edit</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid mb-4">
        <div class="row">
            <div class="col-12">
                <livewire:search-product/>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        @include('utils.alerts')
                        <form id="sale-form" action="{{ route('sales.update', $sale) }}" method="POST">
                            @csrf
                            @method('patch')
                            <div class="form-row">
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label for="reference">Reference <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="reference" required value="{{ $sale->reference }}" readonly>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="from-group">
                                        <div class="form-group">
                                            <label for="customer_id">Customer <span class="text-danger">*</span></label>
                                            <select class="form-control" name="customer_id" id="customer_id" required>
                                                @foreach(\Modules\People\Entities\Customer::all() as $customer)
                                                    <option {{ $sale->customer_id == $customer->id ? 'selected' : '' }} value="{{ $customer->id }}">{{ $customer->customer_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="from-group">
                                        <div class="form-group">
                                            <label for="date">Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" name="date" required value="{{ $sale->date }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <livewire:product-cart :cartInstance="'sale'" :data="$sale"/>

                            <div class="form-row">
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label for="status">Status <span class="text-danger">*</span></label>
                                        <select class="form-control" name="status" id="status" required>
                                            <option {{ $sale->status == 'Pending' ? 'selected' : '' }} value="Pending">Pending</option>
                                            <option {{ $sale->status == 'Shipped' ? 'selected' : '' }} value="Shipped">Shipped</option>
                                            <option {{ $sale->status == 'Completed' ? 'selected' : '' }} value="Completed">Completed</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label for="payment_method">Payment Method <span class="text-danger">*</span></label>
                                        <select class="form-control" name="payment_method" id="payment_method" required onchange="handlePaymentMethodChange()">
                                            <option value="Cash" {{ $sale->payment_method == 'Cash' ? 'selected' : '' }}>Cash</option>
                                            <option value="On Credit" {{ $sale->payment_method == 'On Credit' ? 'selected' : '' }}>On Credit</option>
                                            <option value="Bank Transfer" {{ $sale->payment_method == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                            <option value="Mobile Wallet Transfer" {{ $sale->payment_method == 'Mobile Wallet Transfer' ? 'selected' : '' }}>Mobile Wallet Transfer</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label for="paid_amount">Received Amount <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">{{ settings()->currency->symbol }}</span>
                                            </div>
                                            <input id="paid_amount" type="text" class="form-control text-right" name="paid_amount" required 
                                                value="{{ number_format($sale->paid_amount / 100, 2, '.', '') }}" 
                                                onkeyup="calculateChange()" onchange="calculateChange()" autocomplete="off">
                                            <div class="input-group-append">
                                                <button id="getTotalAmount" class="btn btn-primary" type="button" title="Fill Total Amount">
                                                    <i class="bi bi-check-square"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="note">Note (If Needed)</label>
                                <textarea name="note" id="note" rows="5" class="form-control">{{ $sale->note }}</textarea>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    Update Sale <i class="bi bi-check"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('page_scripts')
    <script src="{{ asset('js/jquery-mask-money.js') }}"></script>
    <script>
        // Initialize payment handling with same settings as POS modal
        $(document).ready(function() {
            // Format the paid amount input
            $('#paid_amount').on('keyup', function(e) {
                // Allow: backspace, delete, tab, escape, enter and .
                if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
                    // Allow: Ctrl+A, Command+A
                    (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) ||
                    // Allow: home, end, left, right, down, up
                    (e.keyCode >= 35 && e.keyCode <= 40)) {
                    return;
                }
                
                // Ensure that it is a number and stop the keypress
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }
            });
            
            // Handle click on "Fill Total" button
            $('#getTotalAmount').click(function() {
                const totalAmount = {{ $sale->total_amount / 100 }};
                $('#paid_amount').val(totalAmount.toFixed(2)).trigger('change');
            });
            
            // Format the input on blur
            $('#paid_amount').on('blur', function() {
                let value = $(this).val();
                if (value && value !== '') {
                    value = parseFloat(value).toFixed(2);
                    $(this).val(value);
                    calculateChange();
                }
            });
            
            // Initialize payment method
            handlePaymentMethodChange();
                
                // Ensure that it is a number and stop the keypress
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }
            });
            
            // Format the input on blur
            $('#paid_amount').on('blur', function() {
                let value = $(this).val();
                if (value && value !== '') {
                    value = parseFloat(value).toFixed(2);
                    $(this).val(value);
                    calculateChange();
                }
            });
            
            // Initialize payment method
            handlePaymentMethodChange();
        });

        function handlePaymentMethodChange() {
            const paymentMethod = document.getElementById('payment_method').value;
            const paidAmountInput = document.getElementById('paid_amount');
            const totalAmount = {{ $sale->total_amount / 100 }};
            
            if (paymentMethod === 'On Credit') {
                // Set paid amount to 0 for credit
                paidAmountInput.value = '0.00';
                paidAmountInput.readOnly = true;
                paidAmountInput.style.backgroundColor = '#f8f9fa';
                
                // Update payment status to Unpaid
                document.querySelector('select[name="payment_status"]').value = 'Unpaid';
            } else {
                // For other methods, set to total amount by default
                paidAmountInput.readOnly = false;
                paidAmountInput.style.backgroundColor = '';
                
                // If current value is 0 (from credit), set to total amount
                if (!paidAmountInput.value || parseFloat(paidAmountInput.value) === 0) {
                    paidAmountInput.value = totalAmount.toFixed(2);
                }
            }
            
            calculateChange();
        }

        function calculateChange() {
            const totalAmount = {{ $sale->total_amount / 100 }};
            const receivedAmount = parseFloat(document.getElementById('paid_amount').value) || 0;
            const dueAmount = totalAmount - receivedAmount;
            
            // Update due amount display
            const dueAmountText = document.getElementById('due_amount_text');
            const changeAmountText = document.getElementById('change_amount_text');
            
            if (dueAmount > 0) {
                dueAmountText.textContent = `Due: ${formatCurrency(dueAmount)}`;
                dueAmountText.className = 'text-danger';
                changeAmountText.textContent = '';
            } else if (dueAmount < 0) {
                dueAmountText.textContent = 'Paid in full';
                dueAmountText.className = 'text-success';
                changeAmountText.textContent = `Change: ${formatCurrency(Math.abs(dueAmount))}`;
                changeAmountText.className = 'text-success';
            } else {
                dueAmountText.textContent = 'Paid in full';
                dueAmountText.className = 'text-success';
                changeAmountText.textContent = '';
            }
            
            // Update payment status
            updatePaymentStatus();
        }
        
        function updatePaymentStatus() {
            const totalAmount = {{ $sale->total_amount / 100 }};
            const receivedAmount = parseFloat(document.getElementById('paid_amount').value) || 0;
            const paymentStatus = document.querySelector('select[name="payment_status"]');
            
            if (receivedAmount >= totalAmount) {
                paymentStatus.value = 'Paid';
            } else if (receivedAmount > 0) {
                paymentStatus.value = 'Partial';
            } else {
                paymentStatus.value = 'Unpaid';
            }
        }
        
        function formatCurrency(amount) {
            return '{{ settings()->currency->symbol }}' + 
                   parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }
        
        // Update form submission to handle the amount
        document.getElementById('sale-form').addEventListener('submit', function(e) {
            const paidAmount = parseFloat(document.getElementById('paid_amount').value) || 0;
            
            if (isNaN(paidAmount) || paidAmount < 0) {
                e.preventDefault();
                alert('Please enter a valid amount');
                return false;
            }
            
            return true;
        });
        // Initialize mask money
        $(document).ready(function() {
            $('#paid_amount').maskMoney({
                prefix: '{{ settings()->currency->symbol }}',
                thousands: '{{ settings()->currency->thousand_separator }}',
                decimal: '{{ settings()->currency->decimal_separator }}',
                allowZero: true,
            });
            $('#paid_amount').maskMoney('mask');
            
            // Initialize payment method
            handlePaymentMethodChange();
        });

        function handlePaymentMethodChange() {
            const paymentMethod = document.getElementById('payment_method').value;
            const paidAmountInput = document.getElementById('paid_amount');
            const totalAmount = {{ $sale->total_amount / 100 }};
            
            if (paymentMethod === 'On Credit') {
                // Set paid amount to 0 for credit
                paidAmountInput.value = '0.00';
                paidAmountInput.readOnly = true;
                paidAmountInput.style.backgroundColor = '#f8f9fa';
                
                // Update payment status to Unpaid
                document.querySelector('select[name="payment_status"]').value = 'Unpaid';
            } else {
                // For other methods, set to total amount
                paidAmountInput.readOnly = false;
                paidAmountInput.style.backgroundColor = '';
                
                // If current value is 0 (from credit), set to total amount
                if (parseFloat(paidAmountInput.value) === 0) {
                    paidAmountInput.value = totalAmount.toFixed(2);
                }
                
                // Update payment status based on amount
                updatePaymentStatus();
            }
            
            // Update the masked input
            $(paidAmountInput).maskMoney('mask', parseFloat(paidAmountInput.value) || 0);
            calculateChange();
        }

        function calculateChange() {
            const totalAmount = {{ $sale->total_amount / 100 }};
            const receivedAmount = parseFloat($('#paid_amount').maskMoney('unmasked')[0]) || 0;
            const dueAmount = totalAmount - receivedAmount;
            
            // Update due amount display
            document.getElementById('due_amount_text').textContent = 
                `Due: ${formatCurrency(dueAmount > 0 ? dueAmount : 0)}`;
                
            // Update payment status
            updatePaymentStatus();
        }
        
        function updatePaymentStatus() {
            const totalAmount = {{ $sale->total_amount / 100 }};
            const receivedAmount = parseFloat($('#paid_amount').maskMoney('unmasked')[0]) || 0;
            const paymentStatus = document.querySelector('select[name="payment_status"]');
            
            if (receivedAmount >= totalAmount) {
                paymentStatus.value = 'Paid';
            } else if (receivedAmount > 0) {
                paymentStatus.value = 'Partial';
            } else {
                paymentStatus.value = 'Unpaid';
            }
        }
        
        function formatCurrency(amount) {
            return '{{ settings()->currency->symbol }}' + 
                   parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        }
        
        // Update form submission to handle masked input
        document.getElementById('sale-form').addEventListener('submit', function(e) {
            const paidAmount = parseFloat($('#paid_amount').maskMoney('unmasked')[0]) || 0;
            const totalAmount = {{ $sale->total_amount / 100 }};
            
            if (paidAmount < 0) {
                e.preventDefault();
                alert('Amount paid cannot be negative');
                return false;
            }
            
            if (paidAmount > totalAmount) {
                e.preventDefault();
                alert('Amount paid cannot be greater than total amount');
                return false;
            }
            
            // Update the hidden paid_amount field with the unmasked value
            const paidAmountInput = document.createElement('input');
            paidAmountInput.type = 'hidden';
            paidAmountInput.name = 'paid_amount';
            paidAmountInput.value = paidAmount;
            this.appendChild(paidAmountInput);
            
            return true;
        });
        document.addEventListener('DOMContentLoaded', function() {
            // Update payment status when payment method or amount changes
            const paymentMethod = document.getElementById('payment_method');
            const paidAmount = document.getElementById('paid_amount');
            const totalAmount = {{ $sale->total_amount / 100 }};
            
            // Update payment status when payment method or amount changes
            function updatePaymentStatus() {
                const amountPaid = parseFloat(paidAmount.value) || 0;
                const paymentStatus = document.querySelector('select[name="payment_status"]');
                
                if (amountPaid >= totalAmount) {
                    paymentStatus.value = 'Paid';
                } else if (amountPaid > 0) {
                    paymentStatus.value = 'Partial';
                } else {
                    paymentStatus.value = 'Unpaid';
                }
                
                // Update due amount display
                const dueAmount = totalAmount - amountPaid;
                document.getElementById('due_amount').textContent = dueAmount.toFixed(2);
            }
            
            // Add event listeners
            paymentMethod.addEventListener('change', updatePaymentStatus);
            paidAmount.addEventListener('input', updatePaymentStatus);
            
            // Initialize payment status
            updatePaymentStatus();
            
            // Form submission
            document.getElementById('sale-form').addEventListener('submit', function(e) {
                const amountPaid = parseFloat(paidAmount.value) || 0;
                
                if (amountPaid < 0) {
                    e.preventDefault();
                    alert('Amount paid cannot be negative');
                    paidAmount.focus();
                    return false;
                }
                
                if (amountPaid > totalAmount) {
                    e.preventDefault();
                    alert('Amount paid cannot be greater than total amount');
                    paidAmount.focus();
                    return false;
                }
                
                return true;
            });
        });
        $(document).ready(function () {
            $('#paid_amount').maskMoney({
                prefix:'{{ settings()->currency->symbol }}',
                thousands:'{{ settings()->currency->thousand_separator }}',
                decimal:'{{ settings()->currency->decimal_separator }}',
                allowZero: true,
            });

            $('#paid_amount').maskMoney('mask');

            $('#sale-form').submit(function () {
                var paid_amount = $('#paid_amount').maskMoney('unmasked')[0];
                $('#paid_amount').val(paid_amount);
            });
        });
    </script>
@endpush
