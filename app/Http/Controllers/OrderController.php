<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource with optional filters.
     */
    public function index(Request $request)
    {
        try {
            // Log the request parameters
            Log::info('Request parameters: ' . json_encode($request->all()));
    
            // Start with a base query that includes all orders with their customers
            $query = Order::with('customer');
    
            // Get total count before any filters
            $totalCount = $query->count();
            Log::info('Total orders in database: ' . $totalCount);
    
            // Apply filters only if they are provided
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
    
            if ($request->filled('customer_id')) {
                $query->where('customer_id', $request->customer_id);
            }
    
            if ($request->filled('product_name')) {
                $query->where('product_name', 'like', '%' . $request->product_name . '%');
            }
    
            if ($request->filled('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            
            if ($request->filled('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }
    
            if ($request->filled('min_quantity')) {
                $query->where('quantity', '>=', $request->min_quantity);
            }
            
            if ($request->filled('max_quantity')) {
                $query->where('quantity', '<=', $request->max_quantity);
            }
    
            if ($request->filled('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }
            
            if ($request->filled('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }
    
            // Get the raw SQL query before sorting
            $sqlBeforeSort = $query->toSql();
            $bindingsBeforeSort = $query->getBindings();
            Log::info('SQL before sorting: ' . $sqlBeforeSort);
            Log::info('Bindings before sorting: ' . json_encode($bindingsBeforeSort));
    
            // Sorting - default to ID ascending to ensure consistent order
            $sortField = $request->get('sort_by', 'id');
            $sortDirection = $request->get('sort_direction', 'asc');
            $query->orderBy($sortField, $sortDirection);
    
            // Get the final SQL query
            $finalSql = $query->toSql();
            $finalBindings = $query->getBindings();
            Log::info('Final SQL: ' . $finalSql);
            Log::info('Final Bindings: ' . json_encode($finalBindings));
    
            // Pagination settings
            $perPage = $request->get('per_page', 3); // Default to 3 items per page
            $page = $request->get('page', 1); // Default to first page
    
            // Get paginated results
            $orders = $query->paginate($perPage, ['*'], 'page', $page);
    
            // Calculate total pages
            $totalPages = ceil($orders->total() / $perPage);
    
            // Debug the data structure (limited to avoid huge logs)
            $sampleData = $orders->items();
            Log::info('Sample Orders Data: ' . json_encode($sampleData));
    
            return response()->json([
                'success' => true,
                'data' => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'total_pages' => $totalPages,
                    'per_page' => $orders->perPage(),
                    'total_items' => $orders->total(),
                    'has_next_page' => $orders->hasMorePages(),
                    'has_previous_page' => $orders->currentPage() > 1,
                    'next_page' => $orders->currentPage() + 1,
                    'previous_page' => $orders->currentPage() - 1
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching orders: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'product_name' => 'required|string|max:255',
                'quantity' => 'required|integer|min:1',
                'price' => 'required|numeric|min:0',
                'status' => 'required|in:pending,shipped'
            ]);

            // Calculate total amount
            $validated['total_amount'] = $validated['quantity'] * $validated['price'];

            $order = Order::create($validated);
            return response()->json($order, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating order: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create order'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $order = Order::with('customer')->findOrFail($id);
            return response()->json($order);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Order not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching order: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch order'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $order = Order::findOrFail($id);
            
            $rules = [];
            
            if ($request->has('customer_id')) {
                $rules['customer_id'] = 'exists:customers,id';
            }
            
            if ($request->has('product_name')) {
                $rules['product_name'] = 'string|max:255';
            }
            
            if ($request->has('quantity')) {
                $rules['quantity'] = 'integer|min:1';
            }
            
            if ($request->has('price')) {
                $rules['price'] = 'numeric|min:0';
            }
            
            if ($request->has('status')) {
                $rules['status'] = 'in:pending,shipped';
            }
            
            $validated = $request->validate($rules);
            
            // Recalculate total amount if quantity or price is updated
            if (isset($validated['quantity']) || isset($validated['price'])) {
                $quantity = $validated['quantity'] ?? $order->quantity;
                $price = $validated['price'] ?? $order->price;
                $validated['total_amount'] = $quantity * $price;
            }
            
            $order->update($validated);
            
            return response()->json($order);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Order not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error updating order: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update order'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $order = Order::findOrFail($id);
            $order->delete();
            return response()->json(null, 204);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Order not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting order: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to delete order'], 500);
        }
    }

    /**
     * Get order statistics
     */
    public function stats()
    {
        try {
            $stats = [
                'total_orders' => Order::count(),
                'total_revenue' => Order::sum(DB::raw('quantity * price')),
                'average_order_value' => Order::avg(DB::raw('quantity * price')),
                'total_products_ordered' => Order::sum('quantity'),
                'orders_by_status' => Order::select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),
                'top_products' => Order::select('product_name', DB::raw('sum(quantity) as total_quantity'))
                    ->groupBy('product_name')
                    ->orderByDesc('total_quantity')
                    ->limit(5)
                    ->get()
                    ->toArray()
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Error fetching order statistics: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch order statistics'], 500);
        }
    }
}
