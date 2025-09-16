<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'notes',
        'created_by'
    ];

    protected $dates = [
        'payment_date',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'amount' => 'decimal:2', // Add this back but with proper handling
    ];
    
    protected $appends = ['formatted_amount', 'formatted_payment_date'];
    
    // Mutator to ensure amount is always stored with 2 decimal places
    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = round((float)$value, 2);
    }
    
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2, '.', '');
    }
    
    public function getFormattedPaymentDateAttribute()
    {
        return $this->payment_date->format('d M Y');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchases()
    {
        return $this->belongsToMany(Purchase::class, 'purchase_payment', 'supplier_payment_id', 'purchase_id')
            ->withPivot('amount')
            ->withTimestamps();
    }
    
    /**
     * Scope a query to only include payments for a specific supplier.
     */
    public function scopeForSupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }
    
    /**
     * Scope a query to only include payments within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }
    
    /**
     * Get the payment method name.
     */
    public function getPaymentMethodNameAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->payment_method));
    }
}
