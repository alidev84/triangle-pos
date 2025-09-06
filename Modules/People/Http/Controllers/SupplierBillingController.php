<?php

namespace Modules\People\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\People\Entities\Supplier;
use Modules\People\Entities\SupplierBillingReport;
use Illuminate\Support\Facades\DB;

class SupplierBillingController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::with('billingReports')->get();
        return view('people::suppliers.billing.index', compact('suppliers'));
    }

    public function show($supplierId)
    {
        $supplier = Supplier::with('billingReports')->findOrFail($supplierId);
        
        $billingReports = $supplier->billingReports()
            ->orderBy('bill_date', 'desc')
            ->get();

        $totalBilled = $supplier->getTotalBilledAmount();
        $totalPaid = $supplier->getTotalPaidAmount();
        $totalRemaining = $supplier->getTotalRemainingAmount();

        return view('people::suppliers.billing.show', compact(
            'supplier', 
            'billingReports', 
            'totalBilled', 
            'totalPaid', 
            'totalRemaining'
        ));
    }

    public function create($supplierId)
    {
        $supplier = Supplier::findOrFail($supplierId);
        return view('people::suppliers.billing.create', compact('supplier'));
    }

    public function store(Request $request, $supplierId)
    {
        $request->validate([
            'bill_reference' => 'required|string|max:255',
            'bill_date' => 'required|date',
            'bill_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $supplier = Supplier::findOrFail($supplierId);

        $billingReport = $supplier->billingReports()->create([
            'bill_reference' => $request->bill_reference,
            'bill_date' => $request->bill_date,
            'bill_amount' => $request->bill_amount,
            'remaining_amount' => $request->bill_amount,
            'payment_status' => 'unpaid',
            'notes' => $request->notes
        ]);

        return redirect()->route('suppliers.billing.show', $supplierId)
            ->with('success', 'Bill added successfully.');
    }

    public function edit($supplierId, $billingId)
    {
        $supplier = Supplier::findOrFail($supplierId);
        $billingReport = $supplier->billingReports()->findOrFail($billingId);
        
        return view('people::suppliers.billing.edit', compact('supplier', 'billingReport'));
    }

    public function update(Request $request, $supplierId, $billingId)
    {
        $request->validate([
            'bill_reference' => 'required|string|max:255',
            'bill_date' => 'required|date',
            'bill_amount' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $supplier = Supplier::findOrFail($supplierId);
        $billingReport = $supplier->billingReports()->findOrFail($billingId);

        $remainingAmount = $request->bill_amount - $request->paid_amount;
        $paymentStatus = 'unpaid';
        
        if ($request->paid_amount > 0) {
            if ($remainingAmount <= 0) {
                $paymentStatus = 'paid';
                $remainingAmount = 0;
            } else {
                $paymentStatus = 'partial';
            }
        }

        $billingReport->update([
            'bill_reference' => $request->bill_reference,
            'bill_date' => $request->bill_date,
            'bill_amount' => $request->bill_amount,
            'paid_amount' => $request->paid_amount,
            'remaining_amount' => $remainingAmount,
            'payment_status' => $paymentStatus,
            'notes' => $request->notes
        ]);

        return redirect()->route('suppliers.billing.show', $supplierId)
            ->with('success', 'Bill updated successfully.');
    }

    public function addPayment(Request $request, $supplierId, $billingId)
    {
        $request->validate([
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date'
        ]);

        $supplier = Supplier::findOrFail($supplierId);
        $billingReport = $supplier->billingReports()->findOrFail($billingId);

        $newPaidAmount = $billingReport->paid_amount + $request->payment_amount;
        $remainingAmount = $billingReport->bill_amount - $newPaidAmount;
        
        $paymentStatus = 'unpaid';
        if ($remainingAmount <= 0) {
            $paymentStatus = 'paid';
            $remainingAmount = 0;
        } else {
            $paymentStatus = 'partial';
        }

        $billingReport->update([
            'paid_amount' => $newPaidAmount,
            'remaining_amount' => $remainingAmount,
            'payment_status' => $paymentStatus
        ]);

        return redirect()->route('suppliers.billing.show', $supplierId)
            ->with('success', 'Payment added successfully.');
    }

    public function destroy($supplierId, $billingId)
    {
        $supplier = Supplier::findOrFail($supplierId);
        $billingReport = $supplier->billingReports()->findOrFail($billingId);
        
        $billingReport->delete();

        return redirect()->route('suppliers.billing.show', $supplierId)
            ->with('success', 'Bill deleted successfully.');
    }
}