<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    protected $casts = [
        'date' => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2'
    ];

    public function supplier()
    {
        return $this->belongsTo(\App\Models\Supplier::class);
    }

    public function payments()
    {
        return $this->belongsToMany(\App\Models\SupplierPayment::class, 'purchase_payment', 'purchase_id', 'supplier_payment_id')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function purchaseDetails() {
        return $this->hasMany(PurchaseDetail::class, 'purchase_id', 'id');
    }

    public function purchasePayments() {
        return $this->hasMany(PurchasePayment::class, 'purchase_id', 'id');
    }

    public static function boot() {
        parent::boot();

        static::creating(function ($model) {
            $number = Purchase::max('id') + 1;
            $model->reference = make_reference_id('PR', $number);
        });
    }

    public function scopeCompleted($query) {
        return $query->where('status', 'Completed');
    }

    public function getShippingAmountAttribute($value) {
        return $value / 100;
    }

    public function getPaidAmountAttribute($value) {
        return $value / 100;
    }

    public function getTotalAmountAttribute($value) {
        return $value / 100;
    }

    public function getDueAmountAttribute($value) {
        return $value / 100;
    }

    public function updatePaymentStatus()
    {
        $totalPaid = $this->payments()->sum('purchase_payment.amount');
        $this->paid_amount = $totalPaid;
        $this->due_amount = $this->total_amount - $totalPaid;
        
        if ($this->due_amount <= 0) {
            $this->payment_status = 'Paid';
        } elseif ($this->paid_amount > 0) {
            $this->payment_status = 'Partial';
        } else {
            $this->payment_status = 'Unpaid';
        }
        
        $this->save();
    }
    
    public function getPaymentStatusAttribute()
    {
        if ($this->due_amount <= 0) {
            return 'Paid';
        } elseif ($this->paid_amount > 0) {
            return 'Partial';
        }
        return 'Unpaid';
    }

    public function getTaxAmountAttribute($value) {
        return $value / 100;
    }

    public function getDiscountAmountAttribute($value) {
        return $value / 100;
    }
}
