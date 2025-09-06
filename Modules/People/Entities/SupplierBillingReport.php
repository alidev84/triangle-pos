<?php

namespace Modules\People\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SupplierBillingReport extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'bill_date' => 'date',
        'bill_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    protected static function newFactory()
    {
        return \Modules\People\Database\factories\SupplierBillingReportFactory::new();
    }
}