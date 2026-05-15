<?php

namespace App\Http\Controllers;

use App\Models\AddedProducts;
use App\Models\BranchProduct;
use Illuminate\Http\Client\ResponseSequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


use App\Services\HistoryLogService;
use Illuminate\Support\Facades\Auth;

class AddedProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
       //
    }

    public function fetchAllSendAddedProducts(Request $request, $branchId)
    {
        $page    = $request->get('page', 1);
        $perPage = $request->get('per_page', 5);
        $search  = $request->query('search', '');

        $query = AddedProducts::where(function ($q) use ($branchId) {
            $q->where('from_branch_id', $branchId)
            ->orWhere('to_branch_id', $branchId);
        })
        ->with('fromBranch', 'toBranch', 'employee', 'product');

        if (!empty($search)) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhereHas('employee', function ($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")
                ->orWhere('lastname', 'ilke', "%{$search}%");
            });
        }

        $addedProducts = $query->orderBy('created_at', 'desc')
                            ->paginate($perPage);

        return response()->json($addedProducts);
    }

    public function fetchSendAddedProducts(Request $request, $branchId, $category)
    {
        // validate the category parameter, if provided
        $page    = $request->get('page', 1);
        $perPage = $request->get('per_page', 10);
        $search  = $request->query('search', '');

        $query = AddedProducts::where(function ($q) use ($branchId) {
            $q->where('from_branch_id', $branchId)
              ->orWhere('to_branch_id', $branchId);
        })
        ->where('category', $category)
        ->with('fromBranch', 'toBranch', 'employee', 'product');

        if (!empty($search)) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhereHas('employee', function ($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")
                  ->orWhere('lastname', 'like', "%{$search}%");
            });
        }

        $addedProducts = $query->get();

        // If category is bread, also include BreadOut records
        if (strtolower($category) === 'bread') {
            $breadOutQuery = \App\Models\BreadOut::where('branch_id', $branchId)
                ->with(['branch', 'product']);
            
            if (!empty($search)) {
                $breadOutQuery->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }

            $breadOuts = $breadOutQuery->get()->map(function($item) {
                return [
                    'id'            => $item->id,
                    'product'       => $item->product,
                    'from_branch'   => $item->branch,
                    'to_branch'     => ['name' => 'Repurposing'],
                    'action'        => 'Pull Out',
                    'added_product' => $item->quantity,
                    'status'        => $item->status,
                    'created_at'    => $item->created_at,
                    'is_repurpose'  => true
                ];
            });

            $merged = $addedProducts->concat($breadOuts)->sortByDesc('created_at');
        } else {
            $merged = $addedProducts->sortByDesc('created_at');
        }

        $offset = ($page - 1) * $perPage;
        $paginatedItems = $merged->slice($offset, $perPage)->values();
        
        return response()->json([
            'data'         => $paginatedItems,
            'current_page' => (int)$page,
            'per_page'     => (int)$perPage,
            'total'        => $merged->count(),
            'last_page'    => ceil($merged->count() / $perPage)
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'employee_id'            => 'required|exists:employees,id',
            'from_branch_id'         => 'required|exists:branches,id',
            'to_branch_id'           => 'required|exists:branches,id',
            'category'               => 'required|string',
            'status'                 => 'required|string',
            'action'                 => 'required|string',
            'remark'                 => 'nullable|string',
            'products'               => 'required|array',
            'products.*.product_id'  => 'required|exists:products,id',
            'products.*.quantity'    => 'required|numeric|min:1',
            'products.*.price'       => 'required|numeric|min:1',
        ]);

        try {
            DB::beginTransaction();

            foreach ($validatedData['products'] as $product) {
                $addedProduct = AddedProducts::create([
                    'employee_id'        => $validatedData['employee_id'],
                    'product_id'         => $product['product_id'],
                    'from_branch_id'     => $validatedData['from_branch_id'],
                    'to_branch_id'       => $validatedData['to_branch_id'],
                    'category'           => $validatedData['category'],
                    'action'             => $validatedData['action'],
                    'price'              => $product['price'],
                    'added_product'      => $product['quantity'], // This is total quantity, store it once
                    'status'             => $validatedData['status'],
                    'remark'             => $validatedData['remark'] ?? null
                ]);

                // 2. Update bread stock from the source branch
                $branchProduct = BranchProduct::where('branches_id', $validatedData['from_branch_id'])
                    ->where('product_id', $product['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$branchProduct) {
                    throw new \Exception('Product stock not found for branch ID' .$validatedData['from_branch_id'] . ' and product ID' .$product['product_id']);
                }

                // Check iff there's enough stock
                if (strtolower($validatedData['action']) !== 'add') {
                    if ($branchProduct->total_quantity < $product['quantity']) {
                        throw new \RuntimeException((json_encode([
                            'type'           => 'insufficient_stock',
                            'product_id'     => $product['product_id'],
                            'product_name'   => $branchProduct->product->name,
                            'available'      => $branchProduct->total_quantity,
                            'requested'      => $product['quantity']
                        ])));
                    }

                    // Deduct the product quantity
                    $branchProduct->total_quantity -= $product['quantity'];
                    $branchProduct->save();

                    // LOG-30 — Branch Product Transfer: Deduction (Source)
                    HistoryLogService::log([
                        'user_id'          => Auth::id(),
                        'report_id'        => $addedProduct->id,
                        'type_of_report'   => 'Product Transfer',
                        'name'             => $branchProduct->product->name,
                        'action'           => 'transferred (out)',
                        'updated_field'    => 'total_quantity',
                        'original_data'    => $branchProduct->total_quantity + $product['quantity'],
                        'updated_data'     => $branchProduct->total_quantity,
                        'designation'      => $validatedData['from_branch_id'],
                        'designation_type' => 'branch',
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Products transferred successfully to another branch.'
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            $decoded = json_decode($e->getMessage(), true);

            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['type'])) {
                return response()->json([
                    'message'    => 'Failed to transfer products.',
                    'errors'     => [
                                'stock' =>
                                    "Insufficient stock for {$decoded['product_name']} " .
                                    "(Available: {$decoded['available']}, " .
                                    "Requested: {$decoded['requested']})"
                    ]
                ], 422);
            }

            // Fallback for unexpected errors
            return response()->json([
                'message'    => 'Failed to transfer products.',
                'errors'     => $e->getMessage()
            ], 500);
        }
    }

    public function receiveProduct(Request $request)
    {
        DB::beginTransaction();
        try {
             $validatedData = $request->validate([
                'id'                     => 'required|exists:added_products,id',
                'employee_id'            => 'required|exists:employees,id',
                'branch_id'              => 'required|exists:branches,id',
                'product_id'             => 'required|exists:products,id',
                'quantity'               => 'required|numeric|min:1',
                'status'                 => 'required|string',
                'remark'                 => 'nullable|string',
            ]);

            // ✅ If status is declined then skip calculation
            if ($validatedData['status'] === 'declined') {
                AddedProducts::where('id', $validatedData['id'])
                    ->update([
                        'received_by' => $validatedData['employee_id'],
                        'status' => $validatedData['status'],
                        'remark' => $validatedData['remark']
                    ]);
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Product was declined, No quantity was updated.'
                ]);
            }

            // ✅ Continue only if NOT declined
            $branchId     = $validatedData['branch_id'];
            $productId    = $validatedData['product_id'];
            $productAdded = $validatedData['quantity'];

            $branchProduct = BranchProduct::where('branches_id', $branchId)
                        ->where('product_id', $productId)
                        ->lockForUpdate()
                        ->first();

            if (!$branchProduct) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found for this branch.',
                ], 404);
            }

            // Add the received product to the total_quantity
            $branchProduct->new_production += $productAdded;
            $branchProduct->total_quantity += $productAdded;
            $branchProduct->save();

            AddedProducts::where('id', $validatedData['id'])
                ->update([
                    'received_by' => $validatedData['employee_id'],
                    'status'      => $validatedData['status'],
                    'remark'      => $validatedData['remark']
                ]);

            // LOG-30 — Branch Product Transfer: Addition (Destination)
            HistoryLogService::log([
                'user_id'          => Auth::id(),
                'report_id'        => $validatedData['id'],
                'type_of_report'   => 'Product Transfer',
                'name'             => $branchProduct->product->name,
                'action'           => 'received (transfer)',
                'updated_field'    => 'total_quantity',
                'original_data'    => $branchProduct->total_quantity - $productAdded,
                'updated_data'     => $branchProduct->total_quantity,
                'designation'      => $branchId,
                'designation_type' => 'branch',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product received and product quantity updated successfully.',
                'product' => $branchProduct
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to receive product. ' . $e->getMessage(),
            ], 500);
        }

    }
}
