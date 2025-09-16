<?php

namespace Modules\PurchasesReturn\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Purchase\Entities\Purchase;

class PurchaseReturnApiController extends Controller
{
    public function getRecentPurchases()
    {
        try {
            $recentPurchases = Purchase::with(['supplier', 'purchaseDetails.product'])
                ->where('status', 'Completed')
                ->orderBy('date', 'desc')
                ->take(10)
                ->get()
                ->map(function($purchase) {
                    return [
                        'id' => $purchase->id,
                        'reference' => $purchase->reference,
                        'supplier_name' => $purchase->supplier->name,
                        'date' => $purchase->date->format('Y-m-d'),
                        'total_amount' => $purchase->total_amount, // Return raw amount, frontend will format
                        'status' => $purchase->status,
                        'payment_status' => $purchase->payment_status,
                        'items_count' => $purchase->purchaseDetails->sum('quantity'),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Recent purchases retrieved successfully',
                'purchases' => $recentPurchases // Changed from 'data' to 'purchases' to match frontend
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve recent purchases',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search for purchases by reference or supplier name
     */
    public function search(Request $request)
    {
        try {
            $query = $request->input('q');
            
            $purchases = Purchase::with(['supplier', 'purchaseDetails.product'])
                ->where('status', 'Completed')
                ->where(function($q) use ($query) {
                    $q->where('reference', 'like', "%{$query}%")
                      ->orWhereHas('supplier', function($q) use ($query) {
                          $q->where('name', 'like', "%{$query}%");
                      });
                })
                ->orderBy('date', 'desc')
                ->take(10)
                ->get()
                ->map(function($purchase) {
                    return [
                        'id' => $purchase->id,
                        'reference' => $purchase->reference,
                        'supplier_name' => $purchase->supplier->name,
                        'date' => $purchase->date->format('Y-m-d'),
                        'total_amount' => $purchase->total_amount, // Return raw amount, frontend will format
                        'status' => $purchase->status,
                        'payment_status' => $purchase->payment_status,
                        'items_count' => $purchase->purchaseDetails->sum('quantity'),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Purchases retrieved successfully',
                'purchases' => $purchases // Changed from 'data' to 'purchases'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search purchases',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get suggestions for purchase references and supplier names
     */
    public function suggestions(Request $request)
    {
        try {
            $query = $request->input('q');
            
            $references = Purchase::select('reference')
                ->where('reference', 'like', "%{$query}%")
                ->where('status', 'Completed')
                ->distinct()
                ->take(5)
                ->get()
                ->map(function($item) {
                    return [
                        'type' => 'reference',
                        'label' => $item->reference,
                        'value' => $item->reference,
                        'icon' => 'bi-receipt'
                    ];
                });
            
            $suppliers = \Modules\People\Entities\Supplier::select('supplier_name as name')
                ->where('supplier_name', 'like', "%{$query}%")
                ->take(5)
                ->get()
                ->map(function($item) {
                    return [
                        'type' => 'supplier',
                        'label' => $item->name,
                        'value' => $item->name,
                        'icon' => 'bi-building'
                    ];
                });
            
            $suggestions = $references->concat($suppliers)->take(5);
            
            return response()->json([
                'success' => true,
                'message' => 'Suggestions retrieved successfully',
                'suggestions' => $suggestions
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get suggestions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get purchase details including products
     */
    public function getPurchaseDetails($id)
    {
        try {
            $purchase = Purchase::with(['supplier', 'purchaseDetails.product'])
                ->findOrFail($id);

            // Format the purchase data for the frontend
            $formattedPurchase = [
                'id' => $purchase->id,
                'reference' => $purchase->reference,
                'date' => $purchase->date->format('Y-m-d'),
                'supplier' => [
                    'id' => $purchase->supplier->id,
                    'name' => $purchase->supplier->supplier_name, // Use correct field name
                    'phone' => $purchase->supplier->phone,
                    'email' => $purchase->supplier->email,
                ],
                'tax_percentage' => $purchase->tax_percentage,
                'discount_percentage' => $purchase->discount_percentage,
                'shipping_amount' => $purchase->shipping_amount,
                'total_amount' => $purchase->total_amount,
                'paid_amount' => $purchase->paid_amount,
                'due_amount' => $purchase->due_amount,
                'status' => $purchase->status,
                'payment_status' => $purchase->payment_status,
                'payment_method' => $purchase->payment_method,
                'note' => $purchase->note,
                'products' => $purchase->purchaseDetails->map(function($detail) {
                    return [
                        'id' => $detail->id,
                        'product_id' => $detail->product_id,
                        'name' => $detail->product->product_name, // Use correct field name
                        'code' => $detail->product->product_code, // Use correct field name
                        'quantity' => $detail->quantity,
                        'price' => $detail->price,
                        'unit_price' => $detail->unit_price,
                        'line_total' => $detail->sub_total, // Map to line_total for frontend
                        'sub_total' => $detail->sub_total,
                        'product_tax_amount' => $detail->product_tax_amount,
                        'product_discount_amount' => $detail->product_discount_amount,
                        'total' => $detail->total ?? $detail->sub_total,
                    ];
                }),
                'created_at' => $purchase->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $purchase->updated_at->format('Y-m-d H:i:s'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Purchase details retrieved successfully',
                'purchase' => $formattedPurchase, // Changed from 'data' to 'purchase'
                'products' => $formattedPurchase['products'] // Add separate products array for easier access
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve purchase details',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}