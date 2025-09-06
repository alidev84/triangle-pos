<?php

namespace Modules\People\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function billingReports()
    {
        return $this->hasMany(SupplierBillingReport::class);
    }

    public function getTotalBilledAmount()
    {
        return $this->billingReports()->sum('bill_amount');
    }

    public function getTotalPaidAmount()
    {
        return $this->billingReports()->sum('paid_amount');
    }

    public function getTotalRemainingAmount()
    {
        return $this->billingReports()->sum('remaining_amount');
    }

    protected static function newFactory() {
        return \Modules\People\Database\factories\SupplierFactory::new();
    }
}
