<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the supplier_payments table if it doesn't exist
        if (!Schema::hasTable('supplier_payments')) {
            Schema::create('supplier_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
                $table->decimal('amount', 15, 2);
                $table->date('payment_date');
                $table->string('payment_method');
                $table->text('notes')->nullable();
                $table->string('reference')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            // Add any missing columns if the table exists
            Schema::table('supplier_payments', function (Blueprint $table) {
                if (!Schema::hasColumn('supplier_payments', 'payment_date')) {
                    $table->date('payment_date')->after('amount');
                }
                if (!Schema::hasColumn('supplier_payments', 'payment_method')) {
                    $table->string('payment_method')->after('payment_date');
                }
                if (!Schema::hasColumn('supplier_payments', 'notes')) {
                    $table->text('notes')->nullable()->after('payment_method');
                }
                if (!Schema::hasColumn('supplier_payments', 'reference')) {
                    $table->string('reference')->nullable()->after('notes');
                }
                if (!Schema::hasColumn('supplier_payments', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->after('reference');
                }
                if (!Schema::hasColumn('supplier_payments', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // Ensure the purchase_payment pivot table exists
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

        // Add paid_amount to purchases if it doesn't exist
        if (!Schema::hasColumn('purchases', 'paid_amount')) {
            Schema::table('purchases', function (Blueprint $table) {
                $table->decimal('paid_amount', 15, 2)->default(0)->after('total_amount');
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
