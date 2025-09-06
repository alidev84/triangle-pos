<?php

namespace Modules\People\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{

    use HasFactory;

    protected $guarded = [];

    public function billingReports()
    {
        return $this->hasMany(CustomerBillingReport::class);
    }

    public function isWholesale()
    {
        return $this->customer_type === 'wholesale';
    }

    public function isRetailer()
    {
        return $this->customer_type === 'retailer';
    }

    protected static function newFactory() {
        return \Modules\People\Database\factories\CustomerFactory::new();
    }

}
