<?php

namespace Modules\People\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Sale\Entities\Sale;
use Modules\People\Entities\CustomerPayment;

class Customer extends Model
{

    use HasFactory;

    protected $guarded = [];

    protected static function newFactory() {
        return \Modules\People\Database\factories\CustomerFactory::new();
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'customer_id', 'id');
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class, 'customer_id', 'id');
    }

    public function getTotalPurchasesAttribute()
    {
        return $this->sales()->sum('total_amount');
    }

    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount');
    }

    public function getOutstandingBalanceAttribute()
    {
        return $this->total_purchases - $this->total_paid;
    }
}
