<?php

namespace Modules\PurchasesReturn\Http\Controllers;

use Modules\PurchasesReturn\DataTables\PurchaseReturnsDataTable;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\People\Entities\Supplier;
use Modules\Product\Entities\Product;
use Modules\PurchasesReturn\Entities\PurchaseReturn;
use Modules\PurchasesReturn\Entities\PurchaseReturnDetail;
use Modules\PurchasesReturn\Entities\PurchaseReturnPayment;
use Modules\PurchasesReturn\Http\Requests\StorePurchaseReturnRequest;
use Modules\PurchasesReturn\Http\Requests\UpdatePurchaseReturnRequest;

class PurchasesReturnController extends Controller
{

    public function index(PurchaseReturnsDataTable $dataTable) {
        abort_if(Gate::denies('access_purchase_returns'), 403);

        return $dataTable->render('purchasesreturn::index');
    }


    public function create() {
        abort_if(Gate::denies('create_purchase_returns'), 403);

        Cart::instance('purchase_return')->destroy();

        return view('purchasesreturn::create');
    }


    public function store(StorePurchaseReturnRequest $request) {
        DB::transaction(function () use ($request) {
            $due_amount = $request->total_amount - $request->paid_amount;

            if ($due_amount == $request->total_amount) {
                $payment_status = 'Unpaid';
            } elseif ($due_amount > 0) {
                $payment_status = 'Partial';
            } else {
                $payment_status = 'Paid';
            }

            $purchase_return = PurchaseReturn::create([
                'date' => $request->date,
                'supplier_id' => $request->supplier_id,
                'supplier_name' => Supplier::findOrFail($request->supplier_id)->supplier_name,
                'tax_percentage' => $request->tax_percentage,
                'discount_percentage' => $request->discount_percentage,
                'shipping_amount' => $request->shipping_amount * 100,
                'paid_amount' => $request->paid_amount * 100,
                'total_amount' => $request->total_amount * 100,
                'due_amount' => $due_amount * 100,
                'status' => $request->status,
                'payment_status' => $payment_status,
                'payment_method' => $request->payment_method,
                'note' => $request->note,
                'tax_amount' => Cart::instance('purchase_return')->tax() * 100,
                'discount_amount' => Cart::instance('purchase_return')->discount() * 100,
            ]);

            foreach (Cart::instance('purchase_return')->content() as $cart_item) {
                PurchaseReturnDetail::create([
                    'purchase_return_id' => $purchase_return->id,
                    'product_id' => $cart_item->id,
                    'product_name' => $cart_item->name,
                    'product_code' => $cart_item->options->code,
                    'quantity' => $cart_item->qty,
                    'price' => $cart_item->price * 100,
                    'unit_price' => $cart_item->options->unit_price * 100,
                    'sub_total' => $cart_item->options->sub_total * 100,
                    'product_discount_amount' => $cart_item->options->product_discount * 100,
                    'product_discount_type' => $cart_item->options->product_discount_type,
                    'product_tax_amount' => $cart_item->options->product_tax * 100,
                ]);

                if ($request->status == 'Shipped' || $request->status == 'Completed') {
                    $product = Product::findOrFail($cart_item->id);
                    $product->update([
                        'product_quantity' => $product->product_quantity - $cart_item->qty
                    ]);
                }
            }

            Cart::instance('purchase_return')->destroy();

            if ($purchase_return->paid_amount > 0) {
                PurchaseReturnPayment::create([
                    'date'               => $request->date,
                    'reference'          => 'INV/' . $purchase_return->reference,
                    'amount'             => $purchase_return->paid_amount,
                    'purchase_return_id' => $purchase_return->id,
                    'payment_method'     => $request->payment_method
                ]);
            }
        });

        toast('Purchase Return Created!', 'success');

        return redirect()->route('purchase-returns.index');
    }


    public function show(PurchaseReturn $purchase_return) {
        abort_if(Gate::denies('show_purchase_returns'), 403);

        $supplier = Supplier::findOrFail($purchase_return->supplier_id);

        return view('purchasesreturn::show', compact('purchase_return', 'supplier'));
    }


    public function edit(PurchaseReturn $purchase_return) {
        abort_if(Gate::denies('edit_purchase_returns'), 403);

        $purchase_return_details = $purchase_return->purchaseReturnDetails;

        Cart::instance('purchase_return')->destroy();

        $cart = Cart::instance('purchase_return');

        foreach ($purchase_return_details as $purchase_return_detail) {
            $cart->add([
                'id'      => $purchase_return_detail->product_id,
                'name'    => $purchase_return_detail->product_name,
                'qty'     => $purchase_return_detail->quantity,
                'price'   => $purchase_return_detail->price,
                'weight'  => 1,
                'options' => [
                    'product_discount' => $purchase_return_detail->product_discount_amount,
                    'product_discount_type' => $purchase_return_detail->product_discount_type,
                    'sub_total'   => $purchase_return_detail->sub_total,
                    'code'        => $purchase_return_detail->product_code,
                    'stock'       => Product::findOrFail($purchase_return_detail->product_id)->product_quantity,
                    'product_tax' => $purchase_return_detail->product_tax_amount,
                    'unit_price'  => $purchase_return_detail->unit_price
                ]
            ]);
        }

        return view('purchasesreturn::edit', compact('purchase_return'));
    }


    public function update(UpdatePurchaseReturnRequest $request, PurchaseReturn $purchase_return) {
        DB::transaction(function () use ($request, $purchase_return) {
            $due_amount = $request->total_amount - $request->paid_amount;

            if ($due_amount == $request->total_amount) {
                $payment_status = 'Unpaid';
            } elseif ($due_amount > 0) {
                $payment_status = 'Partial';
            } else {
                $payment_status = 'Paid';
            }

            foreach ($purchase_return->purchaseReturnDetails as $purchase_return_detail) {
                if ($purchase_return->status == 'Shipped' || $purchase_return->status == 'Completed') {
                    $product = Product::findOrFail($purchase_return_detail->product_id);
                    $product->update([
                        'product_quantity' => $product->product_quantity + $purchase_return_detail->quantity
                    ]);
                }
                $purchase_return_detail->delete();
            }

            $purchase_return->update([
                'date' => $request->date,
                'reference' => $request->reference,
                'supplier_id' => $request->supplier_id,
                'supplier_name' => Supplier::findOrFail($request->supplier_id)->supplier_name,
                'tax_percentage' => $request->tax_percentage,
                'discount_percentage' => $request->discount_percentage,
                'shipping_amount' => $request->shipping_amount * 100,
                'paid_amount' => $request->paid_amount * 100,
                'total_amount' => $request->total_amount * 100,
                'due_amount' => $due_amount * 100,
                'status' => $request->status,
                'payment_status' => $payment_status,
                'payment_method' => $request->payment_method,
                'note' => $request->note,
                'tax_amount' => Cart::instance('purchase_return')->tax() * 100,
                'discount_amount' => Cart::instance('purchase_return')->discount() * 100,
            ]);

            foreach (Cart::instance('purchase_return')->content() as $cart_item) {
                PurchaseReturnDetail::create([
                    'purchase_return_id' => $purchase_return->id,
                    'product_id' => $cart_item->id,
                    'product_name' => $cart_item->name,
                    'product_code' => $cart_item->options->code,
                    'quantity' => $cart_item->qty,
                    'price' => $cart_item->price * 100,
                    'unit_price' => $cart_item->options->unit_price * 100,
                    'sub_total' => $cart_item->options->sub_total * 100,
                    'product_discount_amount' => $cart_item->options->product_discount * 100,
                    'product_discount_type' => $cart_item->options->product_discount_type,
                    'product_tax_amount' => $cart_item->options->product_tax * 100,
                ]);

                if ($request->status == 'Shipped' || $request->status == 'Completed') {
                    $product = Product::findOrFail($cart_item->id);
                    $product->update([
                        'product_quantity' => $product->product_quantity - $cart_item->qty
                    ]);
                }
            }

            Cart::instance('purchase_return')->destroy();
        });

        toast('Purchase Return Updated!', 'info');

        return redirect()->route('purchase-returns.index');
    }


    public function destroy(PurchaseReturn $purchase_return) {
        abort_if(Gate::denies('delete_purchase_returns'), 403);

        $purchase_return->delete();

        toast('Purchase Return Deleted!', 'warning');

        return redirect()->route('purchase-returns.index');
    }

    /**
     * Process a purchase return/modification
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processReturn(\Illuminate\Http\Request $request) {
        DB::beginTransaction();
        
        try {
            $purchase = \Modules\Purchases\Entities\Purchase::findOrFail($request->purchase_id);
            $returnedItems = json_decode($request->returned_items, true);
            
            if (empty($returnedItems)) {
                throw new \Exception('No items to return');
            }
            
            // Calculate total return amount
            $totalReturn = 0;
            $returnDetails = [];
            
            foreach ($returnedItems as $item) {
                if ($item['return_quantity'] <= 0) continue;
                
                $product = \Modules\Product\Entities\Product::findOrFail($item['product_id']);
                $returnAmount = $item['return_quantity'] * $item['unit_price'];
                $totalReturn += $returnAmount;
                
                $returnDetails[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_code' => $product->code,
                    'quantity' => $item['return_quantity'],
                    'price' => $item['unit_price'],
                    'unit_price' => $item['unit_price'],
                    'sub_total' => $returnAmount,
                    'product_discount_amount' => 0,
                    'product_discount_type' => 'fixed',
                    'product_tax_amount' => 0,
                ];
                
                // Update product stock
                $product->update([
                    'product_quantity' => $product->product_quantity - $item['return_quantity']
                ]);
            }
            
            if ($totalReturn <= 0) {
                throw new \Exception('Invalid return amount');
            }
            
            // Create the purchase return
            $purchaseReturn = PurchaseReturn::create([
                'date' => now(),
                'reference' => 'PR-' . date('Ymd') . '-' . strtoupper(uniqid()),
                'supplier_id' => $purchase->supplier_id,
                'supplier_name' => $purchase->supplier->name,
                'tax_percentage' => $purchase->tax_percentage,
                'discount_percentage' => $purchase->discount_percentage,
                'shipping_amount' => 0,
                'paid_amount' => 0,
                'total_amount' => $totalReturn,
                'due_amount' => $totalReturn,
                'status' => 'Completed',
                'payment_status' => 'Unpaid',
                'payment_method' => 'Cash',
                'note' => 'Return for purchase #' . $purchase->reference,
                'purchase_id' => $purchase->id,
                'tax_amount' => 0,
                'discount_amount' => 0,
            ]);
            
            // Add return details
            foreach ($returnDetails as $detail) {
                $purchaseReturn->details()->create($detail);
            }
            
            // Update the original purchase if needed
            // This is where you'd implement the logic to modify the original purchase
            // if that's part of your requirements
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Return processed successfully',
                'data' => [
                    'id' => $purchaseReturn->id,
                    'reference' => $purchaseReturn->reference,
                    'total_amount' => $purchaseReturn->total_amount,
                    'date' => $purchaseReturn->date->format('Y-m-d')
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Purchase return error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process return: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process return/modification for a purchase
     */
    public function processReturnModification(\Illuminate\Http\Request $request) {
        abort_if(Gate::denies('create_purchase_returns'), 403);

        try {
            DB::transaction(function () use ($request) {
                $purchaseId = $request->input('purchase_id');
                $modifications = $request->input('modifications', []);
                $reason = $request->input('reason', '');
                
                // Find the original purchase
                $purchase = \Modules\Purchase\Entities\Purchase::with(['purchaseDetails'])->findOrFail($purchaseId);
                
                $totalRefundAmount = 0;
                $inventoryUpdates = [];
                
                // Process each modification
                foreach ($modifications as $modification) {
                    $productId = $modification['product_id'];
                    $returnQuantity = (int) $modification['return_quantity'];
                    
                    if ($returnQuantity <= 0) continue;
                    
                    // Find the purchase detail for this product
                    $purchaseDetail = $purchase->purchaseDetails->where('product_id', $productId)->first();
                    
                    if (!$purchaseDetail || $returnQuantity > $purchaseDetail->quantity) {
                        throw new \Exception("Invalid return quantity for product ID: {$productId}");
                    }
                    
                    // Calculate refund amount for this product
                    $unitCost = $purchaseDetail->unit_cost / 100; // Convert from cents
                    $refundAmount = $unitCost * $returnQuantity;
                    $totalRefundAmount += $refundAmount;
                    
                    // Update purchase detail quantity
                    $newQuantity = $purchaseDetail->quantity - $returnQuantity;
                    $newSubTotal = $unitCost * $newQuantity * 100; // Convert back to cents
                    
                    if ($newQuantity > 0) {
                        $purchaseDetail->update([
                            'quantity' => $newQuantity,
                            'sub_total' => $newSubTotal
                        ]);
                    } else {
                        // Remove the purchase detail if quantity becomes 0
                        $purchaseDetail->delete();
                    }
                    
                    // Track inventory updates (subtract from inventory for purchase returns)
                    $inventoryUpdates[] = [
                        'product_id' => $productId,
                        'quantity_to_subtract' => $returnQuantity
                    ];
                }
                
                // Update purchase totals
                $remainingTotal = $purchase->purchaseDetails->sum('sub_total');
                $newDueAmount = max(0, $remainingTotal - ($purchase->paid_amount));
                
                // Determine new payment status
                if ($remainingTotal == 0) {
                    $paymentStatus = 'Paid';
                } elseif ($newDueAmount == 0) {
                    $paymentStatus = 'Paid';
                } elseif ($newDueAmount > 0 && $newDueAmount < $remainingTotal) {
                    $paymentStatus = 'Partial';
                } else {
                    $paymentStatus = 'Unpaid';
                }
                
                $purchase->update([
                    'total_amount' => $remainingTotal,
                    'due_amount' => $newDueAmount,
                    'payment_status' => $paymentStatus
                ]);
                
                // Update inventory for returned products (subtract from inventory)
                foreach ($inventoryUpdates as $update) {
                    $product = \Modules\Product\Entities\Product::findOrFail($update['product_id']);
                    $product->update([
                        'product_quantity' => $product->product_quantity - $update['quantity_to_subtract']
                    ]);
                }
                
                // Create return record for tracking
                $purchaseReturn = PurchaseReturn::create([
                    'date' => now()->format('Y-m-d'),
                    'supplier_id' => $purchase->supplier_id,
                    'supplier_name' => $purchase->supplier_name,
                    'tax_percentage' => 0,
                    'discount_percentage' => 0,
                    'shipping_amount' => 0,
                    'paid_amount' => $totalRefundAmount * 100, // Convert to cents
                    'total_amount' => $totalRefundAmount * 100,
                    'due_amount' => 0,
                    'status' => 'Completed',
                    'payment_status' => 'Paid',
                    'payment_method' => 'Cash',
                    'note' => 'Return/Modification: ' . $reason,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                ]);
                
                // Create return detail records
                foreach ($modifications as $modification) {
                    $returnQuantity = (int) $modification['return_quantity'];
                    if ($returnQuantity <= 0) continue;
                    
                    $purchaseDetail = $purchase->purchaseDetails->where('product_id', $modification['product_id'])->first();
                    if ($purchaseDetail) {
                        PurchaseReturnDetail::create([
                            'purchase_return_id' => $purchaseReturn->id,
                            'product_id' => $modification['product_id'],
                            'product_name' => $purchaseDetail->product_name,
                            'product_code' => $purchaseDetail->product_code,
                            'quantity' => $returnQuantity,
                            'price' => $purchaseDetail->unit_cost,
                            'unit_price' => $purchaseDetail->unit_cost,
                            'sub_total' => $purchaseDetail->unit_cost * $returnQuantity,
                            'product_discount_amount' => 0,
                            'product_discount_type' => 'fixed',
                            'product_tax_amount' => 0,
                        ]);
                    }
                }
                
                // Create payment record for the refund
                if ($totalRefundAmount > 0) {
                    PurchaseReturnPayment::create([
                        'date' => now()->format('Y-m-d'),
                        'reference' => 'REF/' . $purchaseReturn->reference,
                        'amount' => $totalRefundAmount * 100,
                        'purchase_return_id' => $purchaseReturn->id,
                        'payment_method' => 'Cash'
                    ]);
                }
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Purchase return processed successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing return: ' . $e->getMessage()
            ], 500);
        }
    }
}
