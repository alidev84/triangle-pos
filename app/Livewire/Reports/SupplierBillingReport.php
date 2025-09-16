<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\People\Entities\Supplier;
use Modules\Purchase\Entities\Purchase;
use Modules\People\Entities\SupplierPayment;

class SupplierBillingReport extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filter properties
    public $suppliers;
    public $start_date;
    public $end_date;
    public $supplier_id;
    public $payment_status;
    
    // Supplier and payment data
    public $selected_supplier = null;
    public $show_payment_modal = false;
    public $show_edit_payment_modal = false;
    public $is_processing = false;
    
    // Payment form fields
    public $payment_amount = 0;
    public $payment_date;
    public $payment_method = 'cash';
    public $payment_notes = '';
    
    // Payment editing fields
    public $editing_payment_id;
    public $editing_payment_amount = 0;
    public $editing_payment_date;
    public $editing_payment_method = 'cash';
    public $editing_payment_notes = '';
    
    // Account status
    public $is_account_settled = false;
    public $is_account_ever_settled = false;
    public $remaining_balance = 0;
    public $total_purchases = 0;
    public $total_paid = 0;
    
    // Computed properties
    protected $listeners = ['payment-updated' => '$refresh'];

    protected function rules()
    {
        return [
            'start_date' => 'required|date|before:end_date',
            'end_date' => 'required|date|after:start_date',
            'payment_amount' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    $value = (float)$value;
                    if ($value <= 0) {
                        $fail('The payment amount must be greater than 0.');
                    }
                    
                    if ($this->supplier_id) {
                        $remaining = $this->remaining_balance;
                        if ($value > $remaining) {
                            $fail('Payment amount cannot exceed the remaining balance of ' . format_currency($remaining));
                        }
                    }
                },
            ],
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
        ];
    }

    public function mount($suppliers = null)
    {
        $this->suppliers = $suppliers ?? Supplier::all();
        $this->start_date = today()->subMonth()->format('Y-m-d');
        $this->end_date = today()->format('Y-m-d');
        $this->payment_date = today()->format('Y-m-d');
        $this->supplier_id = '';
        $this->payment_status = '';
        $this->selected_supplier = null;
        
        if (request()->has('supplier_id')) {
            $this->supplier_id = request('supplier_id');
            $this->updatedSupplierId($this->supplier_id);
        }
        
        $this->calculateAccountStatus();
    }

    public function updatedSupplierId($value)
    {
        $this->selected_supplier = $value ? Supplier::with(['purchases', 'payments'])->find($value) : null;
        $this->calculateAccountStatus();
        
        // Reset payment form
        $this->reset(['payment_amount', 'payment_date', 'payment_method', 'payment_notes']);
        
        // If we have a valid supplier, set default payment date to today
        if ($this->selected_supplier) {
            $this->payment_date = now()->format('Y-m-d');
        }
    }
    
    protected function calculateAccountStatus()
    {
        if (!$this->selected_supplier) {
            $this->resetAccountStatus();
            return;
        }

        // Calculate totals
        $this->calculateTotals();
        
        // Check if account is settled
        $this->is_account_settled = $this->remaining_balance <= 0.01; // Allow for floating point precision
        
        // Check if account was ever settled by checking if supplier has any payments
        $this->is_account_ever_settled = $this->selected_supplier->payments()->exists();
    }
    
    protected function resetAccountStatus()
    {
        $this->is_account_ever_settled = false;
        $this->is_account_settled = false;
        $this->remaining_balance = 0;
        $this->total_purchases = 0;
        $this->total_paid = 0;
    }
    
    protected function calculateTotals()
    {
        if (!$this->selected_supplier) {
            $this->resetAccountStatus();
            return;
        }

        // Get total purchases (convert from cents to dollars if stored as cents)
        $this->total_purchases = $this->selected_supplier->purchases()->sum('total_amount') / 100;
        
        // Get total paid from supplier payments only (no conversion needed)
        $this->total_paid = $this->selected_supplier->payments()->sum('amount');
        
        // Calculate remaining balance
        $this->remaining_balance = max(0, $this->total_purchases - $this->total_paid);
        
        // Update account settled status
        $this->is_account_settled = $this->remaining_balance <= 0.01;
    }
    
    
    public function recordPayment()
    {
        $this->validate([
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
        ]);

        // Debug the incoming amount
        \Log::info('Original payment_amount: ' . $this->payment_amount);
        \Log::info('Type of payment_amount: ' . gettype($this->payment_amount));

        // Clean the amount - remove any formatting and ensure it's a proper decimal
        $cleanAmount = floatval(str_replace(',', '', $this->payment_amount));
        
        \Log::info('Cleaned amount: ' . $cleanAmount);

        // If payment method is "On Credit", set amount to 0
        if ($this->payment_method === 'on_credit') {
            $cleanAmount = 0;
        }
        
        // Check if account is already settled and has new purchases
        $was_ever_settled = $this->is_account_ever_settled;
        $remaining_balance = $this->remaining_balance;
        
        // If account is already settled and no new purchases, don't allow more payments
        if ($was_ever_settled && $remaining_balance <= 0) {
            session()->flash('error', 'Cannot record payment: Account balance is already settled');
            return;
        }
        
        // Ensure we don't overpay
        $payment_amount = min($cleanAmount, $remaining_balance);
        
        if ($payment_amount <= 0 && $this->payment_method !== 'on_credit') {
            session()->flash('error', 'Payment amount must be greater than zero');
            return;
        }
        
        // Start a database transaction to ensure data consistency
        \DB::beginTransaction();
        
        try {
            // Create payment record with exact amount (no conversion)
            $paymentData = [
                'supplier_id' => $this->supplier_id,
                'amount' => $payment_amount, // Store exactly as calculated
                'payment_date' => $this->payment_date,
                'payment_method' => $this->payment_method,
                'notes' => $this->payment_notes,
                'created_by' => auth()->id(),
            ];
            
            \Log::info('Payment data to be saved: ', $paymentData);
            
            // Use DB::table to avoid any model casting issues
            $paymentId = \DB::table('supplier_payments')->insertGetId(array_merge($paymentData, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
            
            \Log::info('Payment saved with ID: ' . $paymentId);
            
            // Verify what was actually saved
            $savedPayment = \DB::table('supplier_payments')->where('id', $paymentId)->first();
            \Log::info('Saved payment amount in DB: ' . $savedPayment->amount);
            
            \DB::commit();
            
            $this->reset(['payment_amount', 'payment_notes', 'show_payment_modal']);
            
            // Recalculate account status
            $this->calculateAccountStatus();
            
            // Dispatch event to update UI
            $this->dispatch('payment-updated');
            
            session()->flash('success', 'Payment recorded successfully. Amount: $' . number_format($payment_amount, 2));
            
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Payment recording error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            session()->flash('error', 'Error recording payment: ' . $e->getMessage());
        }
    }
    
    public function editPayment($paymentId)
    {
        $payment = SupplierPayment::findOrFail($paymentId);
        $this->editing_payment_id = $payment->id;
        $this->editing_payment_amount = $payment->amount;
        
        // Safe date handling
        if ($payment->payment_date) {
            $this->editing_payment_date = $payment->payment_date instanceof \Carbon\Carbon 
                ? $payment->payment_date->format('Y-m-d')
                : \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d');
        } else {
            $this->editing_payment_date = now()->format('Y-m-d');
        }
        
        $this->editing_payment_method = $payment->payment_method;
        $this->editing_payment_notes = $payment->notes ?? '';
        $this->show_edit_payment_modal = true;
    }
    
    public function updatePayment()
    {
        $this->validate([
            'editing_payment_amount' => 'required|numeric|min:0.01',
            'editing_payment_date' => 'required|date',
            'editing_payment_method' => 'required|string',
        ]);

        $payment = SupplierPayment::findOrFail($this->editing_payment_id);
        
        // If payment method is "On Credit", set amount to 0
        $amount = $this->editing_payment_method === 'on_credit' ? 0 : $this->editing_payment_amount;

        $payment->update([
            'amount' => $amount,
            'payment_date' => $this->editing_payment_date,
            'payment_method' => $this->editing_payment_method,
            'notes' => $this->editing_payment_notes,
        ]);

        // Recalculate account status
        $this->calculateAccountStatus();
        
        $this->hideEditPaymentModal();
        session()->flash('success', 'Payment updated successfully.');
    }
    
    public function deletePayment($paymentId)
    {
        $payment = SupplierPayment::findOrFail($paymentId);
        
        \DB::beginTransaction();
        
        try {
            // Safe deletion with null checks
            if ($payment->purchases && $payment->purchases()->exists()) {
                $payment->purchases()->detach();
            }
            
            $payment->delete();
            
            \DB::commit();
            
            $this->calculateAccountStatus();
            session()->flash('success', 'Payment deleted successfully.');
            
        } catch (\Exception $e) {
            \DB::rollBack();
            session()->flash('error', 'Error deleting payment: ' . $e->getMessage());
        }
    }
    
    protected function hasNewPurchasesAfterLastPayment($supplier)
    {
        $last_payment = $supplier->payments()
            ->latest('payment_date')
            ->first();
            
        if (!$last_payment) return true; // No payments yet, so all purchases are "new"
        
        return $supplier->purchases()
            ->where('date', '>', $last_payment->payment_date)
            ->exists();
    }
    
    public function showPaymentModal()
    {
        if (!$this->supplier_id) {
            session()->flash('error', 'Please select a supplier first.');
            return;
        }
        
        $this->reset(['payment_amount', 'payment_notes']);
        $this->payment_date = now()->format('Y-m-d');
        $this->payment_method = 'cash';
        $this->show_payment_modal = true;
    }
    
    public function hidePaymentModal()
    {
        $this->show_payment_modal = false;
    }
    
    public function hideEditPaymentModal()
    {
        $this->reset([
            'editing_payment_id',
            'editing_payment_amount',
            'editing_payment_date',
            'editing_payment_method',
            'editing_payment_notes',
            'show_edit_payment_modal'
        ]);
    }
    
    /**
     * Format money value with 2 decimal places
     */
    protected function formatMoney($amount)
    {
        return number_format($amount, 2, '.', '');
    }
    
    /**
     * Format date for display
     */
    protected function formatDate($date)
    {
        return $date ? \Carbon\Carbon::parse($date)->format('Y-m-d') : null;
    }
    
    protected function applyPaymentToPurchases($supplier, $payment_amount)
    {
        // Ensure payment amount is a float
        $remaining_payment = (float)$payment_amount;
        
        // For accounts that were settled before, only apply to new purchases
        if ($this->is_account_ever_settled) {
            $last_payment_date = $supplier->payments()
                ->latest('payment_date')
                ->value('payment_date');
                
            $purchases = $supplier->purchases()
                ->where('date', '>', $last_payment_date)
                ->whereColumn('paid_amount', '<', 'total_amount')
                ->orderBy('date', 'asc')
                ->get();
        } else {
            // For accounts never settled, apply to all unpaid purchases
            $purchases = $supplier->purchases()
                ->whereColumn('paid_amount', '<', 'total_amount')
                ->orderBy('date', 'asc')
                ->get();
        }

        foreach ($purchases as $purchase) {
            if ($remaining_payment <= 0) break;
            
            // Calculate unpaid amount
            $unpaid_amount = (float)$purchase->total_amount - (float)$purchase->paid_amount;
            $amount_to_apply = min($remaining_payment, $unpaid_amount);
            
            if ($amount_to_apply > 0) {
                // Update paid amount (directly in dollars)
                $purchase->update([
                    'paid_amount' => (float)$purchase->paid_amount + $amount_to_apply
                ]);
                
                $remaining_payment -= $amount_to_apply;
                
                // Update payment status
                $new_paid_amount = (float)$purchase->paid_amount + $amount_to_apply;
                $payment_status = 'Unpaid';
                
                if (abs($new_paid_amount - (float)$purchase->total_amount) < 0.01) { // Account for floating point precision
                    $payment_status = 'Paid';
                } elseif ($new_paid_amount > 0) {
                    $payment_status = 'Partial';
                }
                
                $purchase->update(['payment_status' => $payment_status]);
            }
        }
        
        // Return any unapplied amount (in dollars)
        return max(0, $remaining_payment);
    }
    
    public function render()
    {
        $purchases = collect();
        $supplier = null;
        
        if ($this->supplier_id) {
            // Get supplier with their purchases and payments
            $supplier = Supplier::with(['purchases', 'payments'])->find($this->supplier_id);
            
            if ($supplier) {
                // Get filtered purchases
                $purchases = $supplier->purchases()
                    ->when($this->start_date, function ($query) {
                        return $query->whereDate('date', '>=', $this->start_date);
                    })
                    ->when($this->end_date, function ($query) {
                        return $query->whereDate('date', '<=', $this->end_date);
                    })
                    ->when($this->payment_status, function ($query) {
                        return $query->where('payment_status', $this->payment_status);
                    })
                    ->with('payments') // Eager load payments relationship
                    ->latest('date')
                    ->paginate(10);
                
                // Calculate totals using the same method as in calculateTotals() for consistency
                $this->calculateTotals();
                
                // Update payment status for each purchase
                foreach ($purchases as $purchase) {
                    if ($purchase->paid_amount >= $purchase->total_amount) {
                        $purchase->payment_status = 'Paid';
                    } elseif ($purchase->paid_amount > 0) {
                        $purchase->payment_status = 'Partial';
                    } else {
                        $purchase->payment_status = 'Unpaid';
                    }
                    $purchase->save();
                }
            }
        }
        
        return view('livewire.reports.supplier-billing-report', [
            'purchases' => $purchases,
            'supplier' => $supplier,
            'total_purchases' => $this->total_purchases,
            'total_paid' => $this->total_paid,
            'total_remaining' => $this->remaining_balance
        ]);
    }

    public function generateReport() {
        $this->validate([
            'start_date' => 'required|date|before:end_date',
            'end_date'   => 'required|date|after:start_date',
        ]);
        $this->render();
    }

    public function selectSupplier($supplierId) {
        $this->supplier_id = $supplierId;
        $this->selected_supplier = Supplier::find($supplierId);
        $this->show_payment_modal = false;
    }
}
