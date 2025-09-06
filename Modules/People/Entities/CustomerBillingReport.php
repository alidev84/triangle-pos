<?php

namespace Modules\People\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerBillingReport extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'bill_date' => 'date',
        'bill_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    protected static function newFactory()
    {
        return \Modules\People\Database\factories\CustomerBillingReportFactory::new();
    }
}