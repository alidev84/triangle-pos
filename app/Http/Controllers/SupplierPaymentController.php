<?php

namespace App\Http\Controllers;

use App\Models\SupplierPayment;
use App\Models\Supplier;
use Modules\Purchase\Entities\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SupplierPaymentController extends Controller
{
    /**
     * Display a listing of payments for a supplier.
     */
    public function index(Supplier $supplier)
    {
        $payments = $supplier->payments()
            ->with(['user', 'purchases'])
            ->latest()
            ->paginate(10);

        return response()->json($payments);
    }

    /**
     * Store a new payment and allocate to purchases.
     */
    public function store(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:Cash,On Credit,Bank Transfer,Mobile Wallet Transfer',
            'reference' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'date' => 'required|date',
            'allocations' => 'required|array',
            'allocations.*.purchase_id' => 'required|exists:purchases,id',
            'allocations.*.amount' => 'required|numeric|min:0.01',
        ]);

        return DB::transaction(function () use ($supplier, $validated) {
            // Create the payment
            $payment = $supplier->payments()->create([
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'] ?? null,
                'note' => $validated['note'] ?? null,
                'date' => $validated['date'],
                'user_id' => Auth::id(),
            ]);

            // Allocate payment to purchases
            $totalAllocated = 0;
            foreach ($validated['allocations'] as $allocation) {
                $purchase = Purchase::findOrFail($allocation['purchase_id']);
                
                // Ensure allocation doesn't exceed remaining amount
                $remainingAmount = $purchase->total_amount - $purchase->paid_amount;
                $amountToAllocate = min($allocation['amount'], $remainingAmount);
                
                if ($amountToAllocate > 0) {
                    $payment->purchases()->attach($purchase->id, [
                        'amount' => $amountToAllocate
                    ]);
                    
                    // Update purchase payment status
                    $purchase->updatePaymentStatus();
                    $totalAllocated += $amountToAllocate;
                }
            }

            // Update payment amount to match total allocated
            if ($payment->amount != $totalAllocated) {
                $payment->update(['amount' => $totalAllocated]);
            }

            // Reload relationships
            $payment->load('purchases');

            return response()->json([
                'message' => 'Payment recorded successfully',
                'payment' => $payment
            ], 201);
        });
    }

    /**
     * Display the specified payment.
     */
    public function show(Supplier $supplier, SupplierPayment $payment)
    {
        if ($payment->supplier_id !== $supplier->id) {
            abort(404);
        }

        $payment->load(['user', 'purchases']);
        return response()->json($payment);
    }

    /**
     * Remove the specified payment.
     */
    public function destroy(Supplier $supplier, SupplierPayment $payment)
    {
        if ($payment->supplier_id !== $supplier->id) {
            abort(404);
        }

        return DB::transaction(function () use ($payment) {
            // Get all purchase allocations before deleting
            $purchases = $payment->purchases;
            
            // Delete the payment (purchase_payment records will be deleted by cascade)
            $payment->delete();
            
            // Update payment status for affected purchases
            foreach ($purchases as $purchase) {
                $purchase->updatePaymentStatus();
            }

            return response()->json(['message' => 'Payment deleted successfully']);
        });
    }
}
