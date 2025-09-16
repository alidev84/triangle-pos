<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\People\Entities\Customer;
use Modules\Sale\Entities\Sale;
use Modules\People\Entities\CustomerPayment;
use Livewire\Attributes\On;

class CustomerWholesaleReport extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $customers;
    public $start_date;
    public $end_date;
    public $customer_id;
    public $payment_status;
    public $customer_type = 'retail';
    public $selected_customer;
    public $show_payment_modal = false;
    public $payment_amount;
    public $payment_date;
    public $payment_method = 'cash';
    public $payment_notes;
    public $editing_payment_id;
    public $editing_payment_amount;
    public $editing_payment_date;
    public $editing_payment_method;
    public $editing_payment_notes;
    public $show_edit_payment_modal = false;
    public $isProcessing = false;

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
                    
                    if ($this->customer_id) {
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

    public function getIsAccountEverSettledProperty()
    {
        if (!$this->customer_id) return false;
        
        $customer = Customer::find($this->customer_id);
        if (!$customer) return false;
        
        // Get total sales amount
        $total_sales = $customer->sales()->sum('total_amount') / 100;
        
        // Get total from all payment sources (sales + customer payments)
        $total_from_sales = $customer->sales()->sum('paid_amount') / 100;
        $total_from_payments = $customer->payments()->sum('amount') / 100;
        $combined_total = $total_from_sales + $total_from_payments;
        
        // Account is considered "ever settled" if combined payments equal or exceed total sales
        return $combined_total >= $total_sales;
    }
    
    public function getRemainingBalanceProperty()
    {
        if (!$this->customer_id) return 0;
        
        $customer = Customer::find($this->customer_id);
        if (!$customer) return 0;
        
        // Get total sales amount
        $total_sales = $customer->sales()->sum('total_amount') / 100;
        
        if ($this->is_account_ever_settled) {
            // Account has been settled before - ignore all sales paid_amount
            // Only use customer payment records
            $total_paid = $customer->payments()->sum('amount') / 100;
            
            // If there are new purchases after settlement, include their paid_amount
            $new_purchases = $customer->sales()
                ->where('date', '>', function($query) {
                    $query->select('payment_date')
                        ->from('customer_payments')
                        ->whereColumn('customer_id', 'sales.customer_id')
                        ->latest('payment_date')
                        ->limit(1);
                })
                ->sum('paid_amount') / 100;
                
            $total_paid += $new_purchases;
        } else {
            // Account never settled - use combination of both sources
            $paid_from_sales = $customer->sales()->sum('paid_amount') / 100;
            $paid_from_payments = $customer->payments()->sum('amount') / 100;
            $total_paid = $paid_from_sales + $paid_from_payments;
        }
        
        return max(0, $total_sales - $total_paid);
    }
    
    protected function hasNewPurchasesAfterLastPayment($customer)
    {
        $last_payment = $customer->payments()
            ->latest('payment_date')
            ->first();
            
        if (!$last_payment) return true; // No payments yet, so all purchases are "new"
        
        return $customer->sales()
            ->where('date', '>', $last_payment->payment_date)
            ->exists();
    }
    
    protected function applyPaymentToSales($customer, $payment_amount)
    {
        $remaining_payment = $payment_amount;
        
        // For accounts that were settled before, only apply to new purchases
        if ($this->is_account_ever_settled) {
            $last_payment_date = $customer->payments()
                ->latest('payment_date')
                ->value('payment_date');
                
            $sales = $customer->sales()
                ->where('date', '>', $last_payment_date)
                ->whereColumn('paid_amount', '<', 'total_amount')
                ->orderBy('date', 'asc')
                ->get();
        } else {
            // For accounts never settled, apply to all unpaid sales
            $sales = $customer->sales()
                ->whereColumn('paid_amount', '<', 'total_amount')
                ->orderBy('date', 'asc')
                ->get();
        }

        foreach ($sales as $sale) {
            if ($remaining_payment <= 0) break;
            
            $unpaid_amount = ($sale->total_amount / 100) - ($sale->paid_amount / 100);
            $amount_to_apply = min($remaining_payment, $unpaid_amount);
            
            if ($amount_to_apply > 0) {
                $sale->increment('paid_amount', $amount_to_apply * 100); // Convert back to cents
                $remaining_payment -= $amount_to_apply;
            }
        }
        
        return $remaining_payment; // Return any unapplied amount
    }
    
    public function getIsAccountSettledProperty()
    {
        return $this->remaining_balance <= 0;
    }

    public function mount($customers) {
        $this->customers = $customers;
        $this->start_date = today()->subDays(30)->format('Y-m-d');
        $this->end_date = today()->format('Y-m-d');
        $this->customer_id = '';
        $this->payment_status = '';
        $this->customer_type = 'retail';
        $this->selected_customer = null;
        $this->payment_date = today()->format('Y-m-d');
    }

    public function render() {
        $sales = collect();
        $customer = null;
        $total_sales = 0;
        $total_paid = 0;
        $total_remaining = 0;

        if ($this->customer_id) {
            $customer = Customer::with(['sales', 'payments'])->find($this->customer_id);
            
            if ($customer) {
                $sales = $customer->sales()
                    ->whereDate('date', '>=', $this->start_date)
                    ->whereDate('date', '<=', $this->end_date)
                    ->when($this->payment_status, function ($query) {
                        return $query->where('payment_status', $this->payment_status);
                    })
                    ->orderBy('date', 'desc')
                    ->paginate(10);

                // Get all sales (not filtered by date for total calculations)
                $all_sales = $customer->sales();
                
                // Calculate total sales amount (all time)
                $total_sales = $all_sales->sum('total_amount') / 100;

                if ($this->is_account_ever_settled) {
                    // Account has been settled - only use customer payment records
                    $total_paid = $customer->payments()->sum('amount') / 100;
                    
                    // Include new purchases made after the last settlement
                    $last_payment_date = $customer->payments()
                        ->latest('payment_date')
                        ->value('payment_date');
                    
                    if ($last_payment_date) {
                        $new_purchases = $customer->sales()
                            ->where('date', '>', $last_payment_date)
                            ->sum('paid_amount') / 100;
                        $total_paid += $new_purchases;
                    }
                } else {
                    // Account never settled - use both sales paid_amount and customer payments
                    $paid_from_sales = $all_sales->sum('paid_amount') / 100;
                    $paid_from_payments = $customer->payments()->sum('amount') / 100;
                    $total_paid = $paid_from_sales + $paid_from_payments;
                }

                // Calculate remaining amount (never negative)
                $total_remaining = max(0, $total_sales - $total_paid);
            }
        }

        // Filter customers by type
        $filtered_customers = $this->customers->where('customer_type', $this->customer_type);

        return view('livewire.reports.customer-wholesale-report', [
            'sales' => $sales,
            'customer' => $customer,
            'total_sales' => $total_sales,
            'total_paid' => $total_paid,
            'total_remaining' => $total_remaining,
            'filtered_customers' => $filtered_customers
        ]);
    }

    public function generateReport() {
        $this->validate([
            'start_date' => 'required|date|before:end_date',
            'end_date'   => 'required|date|after:start_date',
        ]);
        $this->render();
    }

    public function selectCustomer($customerId) {
        $this->customer_id = $customerId;
        $this->selected_customer = Customer::find($customerId);
        $this->show_payment_modal = false;
    }

    public function showPaymentModal() {
        if (!$this->customer_id) {
            session()->flash('error', 'Please select a customer first.');
            return;
        }
        $this->show_payment_modal = true;
        $this->payment_amount = '';
        $this->payment_date = today()->format('Y-m-d');
        $this->payment_method = 'cash';
        $this->payment_notes = '';
    }

    public function hidePaymentModal() {
        $this->show_payment_modal = false;
    }

    #[On('payment-updated')]
    public function refreshPaymentStatus()
    {
        // This will force a re-render of the component with updated data
        $this->render();
    }

    public function recordPayment() {
        $this->isProcessing = true;
        
        try {
            $this->validate();
            
            // Get the payment amount as float
            $payment_amount = (float)$this->payment_amount;

            if ($this->customer_id) {
                $customer = Customer::find($this->customer_id);
                
                if ($customer) {
                    // Check if account was ever settled and has new purchases
                    $was_ever_settled = $this->is_account_ever_settled;
                    
                    // Get the remaining balance
                    $remaining_balance = $this->remaining_balance;
                    
                    // If account is already settled and no new purchases, don't allow more payments
                    if ($was_ever_settled && $remaining_balance <= 0) {
                        session()->flash('error', 'Cannot record payment: Account balance is already settled');
                        $this->isProcessing = false;
                        return;
                    }
                    
                    // Ensure we don't overpay
                    $payment_amount = min($payment_amount, $remaining_balance);
                    
                    if ($payment_amount > 0) {
                        // Start a database transaction to ensure data consistency
                        \DB::beginTransaction();
                        
                        try {
                            // Record the payment
                            $payment = $customer->payments()->create([
                                'amount' => $payment_amount,
                                'payment_date' => $this->payment_date,
                                'payment_method' => $this->payment_method,
                                'notes' => $this->payment_notes,
                            ]);

                            // Only update sales records if account was never settled before
                            // or if there are new purchases after the last settlement
                            if (!$was_ever_settled || $this->hasNewPurchasesAfterLastPayment($customer)) {
                                $this->applyPaymentToSales($customer, $payment_amount);
                            }
                            
                            \DB::commit();
                            
                            $this->reset(['payment_amount', 'payment_date', 'payment_notes', 'show_payment_modal']);
                            
                            // Dispatch event to update UI
                            $this->dispatch('payment-updated');
                            $this->dispatch('payment-recorded');
                            
                            // Show success message
                            session()->flash('message', 'Payment recorded successfully.');
                            
                        } catch (\Exception $e) {
                            \DB::rollBack();
                            throw $e; // Re-throw to be caught by the outer try-catch
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error recording payment: ' . $e->getMessage());
        } finally {
            $this->isProcessing = false;
        }
    }

    public function editPayment($paymentId) {
        $payment = CustomerPayment::findOrFail($paymentId);
        $this->editing_payment_id = $payment->id;
        $this->editing_payment_amount = $payment->amount;
        $this->editing_payment_date = $payment->payment_date;
        $this->editing_payment_method = $payment->payment_method;
        $this->editing_payment_notes = $payment->notes;
        $this->show_edit_payment_modal = true;
    }

    public function updatePayment() {
        $this->validate([
            'editing_payment_amount' => 'required|numeric|min:0.01',
            'editing_payment_date' => 'required|date',
            'editing_payment_method' => 'required|string',
        ]);

        $payment = CustomerPayment::findOrFail($this->editing_payment_id);
        
        // If payment method is "On Credit", set amount to 0
        $amount = $this->editing_payment_method === 'on_credit' ? 0 : $this->editing_payment_amount;

        $payment->update([
            'amount' => $amount,
            'payment_date' => $this->editing_payment_date,
            'payment_method' => $this->editing_payment_method,
            'notes' => $this->editing_payment_notes,
        ]);

        session()->flash('success', 'Payment updated successfully.');
        $this->hideEditPaymentModal();
        $this->render();
    }

    public function hideEditPaymentModal() {
        $this->show_edit_payment_modal = false;
        $this->editing_payment_id = null;
        $this->editing_payment_amount = null;
        $this->editing_payment_date = null;
        $this->editing_payment_method = null;
        $this->editing_payment_notes = null;
    }

    public function deletePayment($paymentId) {
        CustomerPayment::findOrFail($paymentId)->delete();
        session()->flash('success', 'Payment deleted successfully.');
        $this->render();
    }
}
