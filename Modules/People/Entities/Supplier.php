<?php

namespace Modules\People\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Purchase\Entities\Purchase;
use App\Models\SupplierPayment as AppSupplierPayment;

class Supplier extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory() {
        return \Modules\People\Database\factories\SupplierFactory::new();
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'supplier_id', 'id');
    }

    /**
     * Get all payments for the supplier.
     */
    public function payments()
    {
        return $this->hasMany(AppSupplierPayment::class, 'supplier_id', 'id');
    }
    
    /**
     * Get all purchases with their payment status.
     */
    public function purchasesWithPayments()
    {
        return $this->purchases()
            ->with('payments')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($purchase) {
                $purchase->paid_amount = $purchase->payments->sum('pivot.amount');
                $purchase->due_amount = $purchase->total_amount - $purchase->paid_amount;
                $purchase->payment_status = $purchase->due_amount <= 0 ? 'Paid' : 
                                         ($purchase->paid_amount > 0 ? 'Partial' : 'Unpaid');
                return $purchase;
            });
    }
    
    /**
     * Get the total amount owed by the supplier.
     */
    public function getTotalOwedAttribute()
    {
        return $this->total_purchases - $this->total_paid_amount;
    }
    
    /**
     * Get the total amount paid to the supplier.
     */
    public function getTotalPurchasesAttribute()
    {
        return $this->purchases()->sum('total_amount');
    }

    /**
     * Get the total amount paid to the supplier.
     */
    public function getTotalPaidAmountAttribute()
    {
        return $this->payments()->sum('amount');
    }

    public function getOutstandingBalanceAttribute()
    {
        return $this->total_owed;
    }
    
    /**
     * Get all unpaid or partially paid purchases.
     */
    public function getUnpaidPurchasesAttribute()
    {
        return $this->purchases()
            ->whereRaw('total_amount > COALESCE((SELECT SUM(pp.amount) FROM purchase_payment pp WHERE pp.purchase_id = purchases.id), 0)')
            ->orderBy('date', 'asc')
            ->get()
            ->map(function ($purchase) {
                $purchase->paid_amount = $purchase->payments->sum('pivot.amount');
                $purchase->due_amount = $purchase->total_amount - $purchase->paid_amount;
                return $purchase;
            });
    }
}
