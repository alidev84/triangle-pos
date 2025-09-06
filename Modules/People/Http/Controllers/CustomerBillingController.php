<?php

namespace Modules\People\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\People\Entities\Customer;
use Modules\People\Entities\CustomerBillingReport;
use Illuminate\Support\Facades\DB;

class CustomerBillingController extends Controller
{
    public function index()
    {
        $customers = Customer::where('customer_type', 'wholesale')
            ->with('billingReports')
            ->get();
        return view('people::customers.billing.index', compact('customers'));
    }

    public function show($customerId)
    {
        $customer = Customer::with('billingReports')->findOrFail($customerId);
        
        if (!$customer->isWholesale()) {
            return redirect()->back()->with('error', 'This customer is not a wholesale customer.');
        }
        
        $billingReports = $customer->billingReports()
            ->orderBy('bill_date', 'desc')
            ->get();

        $totalBilled = $customer->billingReports()->sum('bill_amount');
        $totalPaid = $customer->billingReports()->sum('paid_amount');
        $totalRemaining = $customer->billingReports()->sum('remaining_amount');

        return view('people::customers.billing.show', compact(
            'customer', 
            'billingReports', 
            'totalBilled', 
            'totalPaid', 
            'totalRemaining'
        ));
    }

    public function create($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        
        if (!$customer->isWholesale()) {
            return redirect()->back()->with('error', 'This customer is not a wholesale customer.');
        }
        
        return view('people::customers.billing.create', compact('customer'));
    }

    public function store(Request $request, $customerId)
    {
        $request->validate([
            'bill_reference' => 'required|string|max:255',
            'bill_date' => 'required|date',
            'bill_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $customer = Customer::findOrFail($customerId);
        
        if (!$customer->isWholesale()) {
            return redirect()->back()->with('error', 'This customer is not a wholesale customer.');
        }

        $billingReport = $customer->billingReports()->create([
            'bill_reference' => $request->bill_reference,
            'bill_date' => $request->bill_date,
            'bill_amount' => $request->bill_amount,
            'remaining_amount' => $request->bill_amount,
            'payment_status' => 'unpaid',
            'notes' => $request->notes
        ]);

        return redirect()->route('customers.billing.show', $customerId)
            ->with('success', 'Bill added successfully.');
    }

    public function edit($customerId, $billingId)
    {
        $customer = Customer::findOrFail($customerId);
        $billingReport = $customer->billingReports()->findOrFail($billingId);
        
        return view('people::customers.billing.edit', compact('customer', 'billingReport'));
    }

    public function update(Request $request, $customerId, $billingId)
    {
        $request->validate([
            'bill_reference' => 'required|string|max:255',
            'bill_date' => 'required|date',
            'bill_amount' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $customer = Customer::findOrFail($customerId);
        $billingReport = $customer->billingReports()->findOrFail($billingId);

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

        return redirect()->route('customers.billing.show', $customerId)
            ->with('success', 'Bill updated successfully.');
    }

    public function addPayment(Request $request, $customerId, $billingId)
    {
        $request->validate([
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date'
        ]);

        $customer = Customer::findOrFail($customerId);
        $billingReport = $customer->billingReports()->findOrFail($billingId);

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

        return redirect()->route('customers.billing.show', $customerId)
            ->with('success', 'Payment added successfully.');
    }

    public function destroy($customerId, $billingId)
    {
        $customer = Customer::findOrFail($customerId);
        $billingReport = $customer->billingReports()->findOrFail($billingId);
        
        $billingReport->delete();

        return redirect()->route('customers.billing.show', $customerId)
            ->with('success', 'Bill deleted successfully.');
    }
}