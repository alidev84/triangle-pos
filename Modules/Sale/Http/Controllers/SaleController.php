<?php

namespace Modules\Sale\Http\Controllers;

use Modules\Sale\DataTables\SalesDataTable;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\People\Entities\Customer;
use Modules\Product\Entities\Product;
use Modules\Sale\Entities\Sale;
use Modules\Sale\Entities\SaleDetails;
use Modules\Sale\Entities\SalePayment;
use Modules\Sale\Http\Requests\StoreSaleRequest;
use Modules\Sale\Http\Requests\UpdateSaleRequest;

class SaleController extends Controller
{

    public function index(SalesDataTable $dataTable) {
        abort_if(Gate::denies('access_sales'), 403);

        return $dataTable->render('sale::index');
    }


    public function create() {
        abort_if(Gate::denies('create_sales'), 403);

        Cart::instance('sale')->destroy();

        return view('sale::create');
    }


    public function store(StoreSaleRequest $request) {
        DB::transaction(function () use ($request) {
            // Calculate due amount only for 'On Credit' payments or when paid less than total
            $due_amount = 0;
            if ($request->payment_method === 'On Credit' || $request->paid_amount < $request->total_amount) {
                $due_amount = $request->total_amount - $request->paid_amount;
            }

            if ($request->payment_method === 'On Credit' || $due_amount == $request->total_amount) {
                $payment_status = 'Unpaid';
            } elseif ($due_amount > 0) {
                $payment_status = 'Partial';
            } else {
                $payment_status = 'Paid';
            }

            $sale = Sale::create([
                'date' => $request->date,
                'customer_id' => $request->customer_id,
                'customer_name' => Customer::findOrFail($request->customer_id)->customer_name,
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
                'tax_amount' => Cart::instance('sale')->tax() * 100,
                'discount_amount' => Cart::instance('sale')->discount() * 100,
            ]);

            foreach (Cart::instance('sale')->content() as $cart_item) {
                SaleDetails::create([
                    'sale_id' => $sale->id,
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

            Cart::instance('sale')->destroy();

            if ($sale->paid_amount > 0) {
                SalePayment::create([
                    'date' => $request->date,
                    'reference' => 'INV/'.$sale->reference,
                    'amount' => $sale->paid_amount,
                    'sale_id' => $sale->id,
                    'payment_method' => $request->payment_method
                ]);
            }
        });

        toast('Sale Created!', 'success');

        return redirect()->route('sales.index');
    }


    public function show(Sale $sale) {
        abort_if(Gate::denies('show_sales'), 403);

        $customer = Customer::findOrFail($sale->customer_id);

        return view('sale::show', compact('sale', 'customer'));
    }


    public function edit(Sale $sale) {
        abort_if(Gate::denies('edit_sales'), 403);

        $sale_details = $sale->saleDetails;

        Cart::instance('sale')->destroy();

        $cart = Cart::instance('sale');

        foreach ($sale_details as $sale_detail) {
            $cart->add([
                'id'      => $sale_detail->product_id,
                'name'    => $sale_detail->product_name,
                'qty'     => $sale_detail->quantity,
                'price'   => $sale_detail->price,
                'weight'  => 1,
                'options' => [
                    'product_discount' => $sale_detail->product_discount_amount,
                    'product_discount_type' => $sale_detail->product_discount_type,
                    'sub_total'   => $sale_detail->sub_total,
                    'code'        => $sale_detail->product_code,
                    'stock'       => Product::findOrFail($sale_detail->product_id)->product_quantity,
                    'product_tax' => $sale_detail->product_tax_amount,
                    'unit_price'  => $sale_detail->unit_price
                ]
            ]);
        }

        return view('sale::edit', compact('sale'));
    }


    public function update(UpdateSaleRequest $request, Sale $sale) {
        DB::transaction(function () use ($request, $sale) {
            // Calculate due amount only for 'On Credit' payments or when paid less than total
            $due_amount = 0;
            if ($request->payment_method === 'On Credit' || $request->paid_amount < $request->total_amount) {
                $due_amount = $request->total_amount - $request->paid_amount;
            }

            if ($request->payment_method === 'On Credit' || $due_amount == $request->total_amount) {
                $payment_status = 'Unpaid';
            } elseif ($due_amount > 0) {
                $payment_status = 'Partial';
            } else {
                $payment_status = 'Paid';
            }

            foreach ($sale->saleDetails as $sale_detail) {
                if ($sale->status == 'Shipped' || $sale->status == 'Completed') {
                    $product = Product::findOrFail($sale_detail->product_id);
                    $product->update([
                        'product_quantity' => $product->product_quantity + $sale_detail->quantity
                    ]);
                }
                $sale_detail->delete();
            }

            $sale->update([
                'date' => $request->date,
                'reference' => $request->reference,
                'customer_id' => $request->customer_id,
                'customer_name' => Customer::findOrFail($request->customer_id)->customer_name,
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
                'tax_amount' => Cart::instance('sale')->tax() * 100,
                'discount_amount' => Cart::instance('sale')->discount() * 100,
            ]);

            foreach (Cart::instance('sale')->content() as $cart_item) {
                SaleDetails::create([
                    'sale_id' => $sale->id,
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

            Cart::instance('sale')->destroy();
        });

        toast('Sale Updated!', 'info');

        return redirect()->route('sales.index');
    }


    public function destroy(Sale $sale) {
        abort_if(Gate::denies('delete_sales'), 403);

        $sale->delete();

        toast('Sale Deleted!', 'warning');

        return redirect()->route('sales.index');
    }

    /**
     * Search for sales by reference or customer name (API endpoint)
     */
    public function searchTransactions(\Illuminate\Http\Request $request) {
        try {
            $query = $request->get('q', '');
            
            if (empty($query)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Search query is required'
                ]);
            }
            
            $sales = Sale::where(function($q) use ($query) {
                    $q->where('reference', 'LIKE', '%' . $query . '%')
                      ->orWhere('customer_name', 'LIKE', '%' . $query . '%');
                })
                ->with(['saleDetails.product'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            
            if ($sales->count() > 0) {
                $results = $sales->map(function($sale) {
                    return [
                        'id' => $sale->id,
                        'reference' => $sale->reference,
                        'customer_id' => $sale->customer_id,
                        'customer_name' => $sale->customer_name,
                        'date' => $sale->date,
                        'total_amount' => $sale->total_amount,
                        'status' => $sale->status,
                        'payment_status' => $sale->payment_status,
                        'products' => $sale->saleDetails->map(function($detail) {
                            return [
                                'id' => $detail->product_id,
                                'name' => $detail->product->product_name,
                                'quantity' => $detail->quantity,
                                'unit_price' => $detail->unit_price,
                                'line_total' => $detail->sub_total
                            ];
                        })
                    ];
                });
                
                return response()->json([
                    'success' => true,
                    'sales' => $results
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No sales found matching your search'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching for sales: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get search suggestions (API endpoint)
     */
    public function getSuggestions(\Illuminate\Http\Request $request) {
        try {
            $query = $request->get('q', '');
            
            if (strlen($query) < 2) {
                return response()->json([
                    'success' => true,
                    'suggestions' => []
                ]);
            }
            
            $references = Sale::where('reference', 'LIKE', '%' . $query . '%')
                            ->select('reference')
                            ->distinct()
                            ->limit(5)
                            ->pluck('reference');
                            
            $customers = Sale::where('customer_name', 'LIKE', '%' . $query . '%')
                           ->select('customer_name')
                           ->distinct()
                           ->limit(5)
                           ->pluck('customer_name');
            
            $suggestions = [];
            
            foreach ($references as $ref) {
                $suggestions[] = [
                    'type' => 'reference',
                    'value' => $ref,
                    'label' => 'Ref: ' . $ref
                ];
            }
            
            foreach ($customers as $customer) {
                $suggestions[] = [
                    'type' => 'customer',
                    'value' => $customer,
                    'label' => 'Customer: ' . $customer
                ];
            }
            
            return response()->json([
                'success' => true,
                'suggestions' => $suggestions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting suggestions: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get recent sales for selection (API endpoint)
     */
    public function getRecentSales() {
        try {
            $sales = Sale::orderBy('created_at', 'desc')
                        ->limit(20)
                        ->get()
                        ->map(function($sale) {
                            return [
                                'id' => $sale->id,
                                'reference' => $sale->reference,
                                'customer_id' => $sale->customer_id,
                                'customer_name' => $sale->customer_name,
                                'date' => $sale->date,
                                'total_amount' => $sale->total_amount,
                                'status' => $sale->status,
                                'payment_status' => $sale->payment_status
                            ];
                        });
            
            return response()->json([
                'success' => true,
                'sales' => $sales
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading sales: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get sale products (API endpoint)
     */
    public function getSaleProducts($id) {
        try {
            $sale = Sale::with(['saleDetails.product'])->findOrFail($id);
            
            $products = $sale->saleDetails->map(function($detail) {
                return [
                    'id' => $detail->product_id,
                    'name' => $detail->product->product_name,
                    'quantity' => $detail->quantity,
                    'unit_price' => $detail->unit_price,
                    'line_total' => $detail->sub_total
                ];
            });
            
            return response()->json([
                'success' => true,
                'products' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading sale products: ' . $e->getMessage()
            ]);
        }
    }
}
