<?php

namespace App\Http\Controllers;

use App\Models\AddedProducts;
use App\Models\BranchProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class AddedProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function fetchSendAddedProducts(Request $request,$branchId, $category)
    {
        // validate the category parameter, if provided
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 5);
        $search = $request->query('search', '');

        $query = AddedProducts::where(function ($q) use ($branchId) {
                $q->where('from_branch_id', $branchId)
                ->orWhere('to_branch_id', $branchId);
            })
            ->where('category', $category)
            ->with('fromBranch', 'toBranch', 'employee',  'product');

                // ->where('status', 'pending')


        if (!empty($search)) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhereHas('employee', function ($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")
                  ->orWhere('lastname', 'like', "%{$search}%");
            });
        }

        $addedProducts = $query->orderBy('created_at', 'desc')
                                ->paginate($perPage);

        return response()->json($addedProducts);
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
                AddedProducts::create([
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
                    'message' => 'Failed to transfer products.',
                    'errors' => [
                        'stock' =>
                            "Insufficient stock for {$decoded['product_name']} " .
                            "(Available: {$decoded['available']}, " .
                            "Requested: {$decoded['requested']})"
                    ]
                ], 422);
            }

            // Fallback for unexpected errors
            return response()->json([
                'message' => 'Failed to transfer products.',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function receiveProduct(Request $request)
    {
        try {
             $validatedData = $request->validate([
                'id'                     => 'required|exists:added_products,id',
                'empoyee_id'             => 'required|exists:employees,id',
                'branch_id'              => 'required|exists:branches,id',
                'product_id'             => 'required|exists:products,id',
                'quantity'               => 'required|numeric|min:1',
                'status'                 => 'required|string',
                'remark'                 => 'nullable|string',
            ]);

            $branchId     = $validatedData['branch_id'];
            $productId    = $validatedData['product_id'];
            $productAdded = $validatedData['quantity'];

            $branchProduct = BranchProduct::where('branches_id', $branchId)
                        ->where('product_id', $productId)
                        ->first();

            if (!$branchProduct) {
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
                    'received_by' => $validatedData['empoyee_id'],
                    'status'      => $validatedData['status'],
                    'remark'      => $validatedData['remark']
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Product received and product quantity updated successfully.',
                'product' => $branchProduct
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to receive product. ' . $e->getMessage(),
            ], 500);
        }

    }
}
