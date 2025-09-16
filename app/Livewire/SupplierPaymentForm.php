<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Supplier;
use Modules\People\Entities\Supplier as SupplierEntity;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\Auth;

class SupplierPaymentForm extends Component
{
    public $supplierId;
    public $supplier;
    public $amount = 0;
    public $paymentMethod = 'Cash';
    public $reference = '';
    public $note = '';
    public $paymentDate;
    public $allocations = [];
    public $availablePaymentMethods = [
        'Cash' => 'Cash',
        'Bank Transfer' => 'Bank Transfer',
        'Mobile Wallet' => 'Mobile Wallet',
        'Other' => 'Other'
    ];

    protected $rules = [
        'amount' => 'required|numeric|min:0.01',
        'paymentMethod' => 'required|string',
        'reference' => 'nullable|string|max:255',
        'note' => 'nullable|string',
        'paymentDate' => 'required|date',
        'allocations' => 'required|array|min:1',
        'allocations.*.amount' => 'required|numeric|min:0.01'
    ];

    public function mount($supplierId)
    {
        $this->supplierId = $supplierId;
        $this->supplier = SupplierEntity::findOrFail($supplierId);
        $this->paymentDate = now()->format('Y-m-d');
        $this->loadUnpaidPurchases();
    }

    public function loadUnpaidPurchases()
    {
        $this->allocations = collect($this->supplier->unpaidPurchases)
            ->map(function ($purchase) {
                return [
                    'purchase_id' => $purchase->id,
                    'reference' => $purchase->reference,
                    'total_amount' => $purchase->total_amount,
                    'paid_amount' => $purchase->paid_amount ?? 0,
                    'due_amount' => $purchase->due_amount ?? $purchase->total_amount,
                    'amount' => 0
                ];
            })->toArray();
    }

    public function updated($propertyName)
    {
        if ($propertyName === 'amount') {
            $this->distributePayment();
        }
    }

    public function distributePayment()
    {
        $remainingAmount = (float)$this->amount;
        
        foreach ($this->allocations as &$allocation) {
            if ($remainingAmount <= 0) {
                $allocation['amount'] = 0;
                continue;
            }
            
            $maxAllocatable = min($allocation['due_amount'], $remainingAmount);
            $allocation['amount'] = $maxAllocatable;
            $remainingAmount -= $maxAllocatable;
        }
    }

    public function savePayment()
    {
        $this->validate();

        try {
            $response = \Http::post(route('api.suppliers.payments.store', $this->supplierId), [
                'amount' => $this->amount,
                'payment_method' => $this->paymentMethod,
                'reference' => $this->reference,
                'note' => $this->note,
                'date' => $this->paymentDate,
                'allocations' => collect($this->allocations)
                    ->filter(fn($allocation) => $allocation['amount'] > 0)
                    ->map(fn($allocation) => [
                        'purchase_id' => $allocation['purchase_id'],
                        'amount' => $allocation['amount']
                    ])->values()->toArray()
            ]);

            if ($response->successful()) {
                session()->flash('success', 'Payment recorded successfully');
                $this->reset(['amount', 'reference', 'note']);
                $this->loadUnpaidPurchases();
                $this->dispatch('paymentRecorded');
            } else {
                $this->addError('payment', 'Failed to record payment: ' . ($response->json()['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->addError('payment', 'Error: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.supplier-payment-form', [
            'totalAllocated' => collect($this->allocations)->sum('amount'),
            'remainingAmount' => (float)$this->amount - collect($this->allocations)->sum('amount')
        ]);
    }
}
