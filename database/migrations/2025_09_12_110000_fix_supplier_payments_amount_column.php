<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Change amount column to decimal(10,2) if it exists and is not already decimal
        if (Schema::hasColumn('supplier_payments', 'amount')) {
            Schema::table('supplier_payments', function (Blueprint $table) {
                $table->decimal('amount', 10, 2)->change();
            });
        }
    }

    public function down()
    {
        // No need to revert as we're just ensuring the correct data type
    }
};
