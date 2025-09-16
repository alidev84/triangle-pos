<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add paid_amount column to purchases table if it doesn't exist
        if (!Schema::hasColumn('purchases', 'paid_amount')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->decimal('paid_amount', 15, 2)->default(0)->after('total_amount');
            });
        }

        // Create purchase_payment table if it doesn't exist
        if (!Schema::hasTable('purchase_payment')) {
            Schema::create('purchase_payment', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_id')->constrained()->onDelete('cascade');
                $table->foreignId('supplier_payment_id')->constrained('supplier_payments')->onDelete('cascade');
                $table->decimal('amount', 15, 2);
                $table->timestamps();
                
                $table->unique(['purchase_id', 'supplier_payment_id']);
            });
        }

        // Add soft deletes to supplier_payments if not exists
        if (Schema::hasTable('supplier_payments') && !Schema::hasColumn('supplier_payments', 'deleted_at')) {
            Schema::table('supplier_payments', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We won't drop any columns or tables in the down method
        // to prevent data loss. Manual cleanup would be needed if required.
    }
};
