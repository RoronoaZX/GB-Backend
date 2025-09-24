<?php

namespace App\Http\Controllers;

use App\Models\BranchProduct;
use App\Models\BreadAdded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\TryCatch;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BreadAddedController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function fetchPendingSendBread(Request $request)
    {
        $validatedData = $request->validate([
            'branch_id' => 'required|integer',
        ]);

        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 5);
        $search = $request->get('search');

        // Build base query
        $query = BreadAdded::with(['employee', 'product', 'fromBranch', 'toBranch'])
                    ->where('from_branch_id', $validatedData['branch_id']);

        // Apply search filter for fromBranch and toBranch names
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('fromBranch', function ($q2) use ($search) {
                    $q2->where('name', 'like', '%' . $search . '%');
                })->orWhereHas('toBranch', function ($q2) use ($search) {
                    $q2->where('name', 'like', '%' . $search . '%');
                });
            });
        }

        $allPending = $query->get();

        if ($perPage == 0) {
            return response()->json([
                'data'           => $allPending,
                'total'          => $allPending->count(),
                'per_page'       => $allPending->count(),
                'current_page'   => 1,
                'last_page'      => 1,
            ]);
        }

        // Manual pagination
        $paginated = new LengthAwarePaginator(
            $allPending->forPage($page, $perPage)->values(),
            $allPending->count(),
            $perPage,
            $page,
            ['path' => url()->current()]
        );

        return response()->json($paginated);
    }
    public function receivedBread(Request $request)
    {
        try {
            $validated = $request->validate([
                'status'         => 'required|string',
                'branchId'       => 'required|integer',
                'report_id'      => 'required|integer',
                'product_id'     => 'required|integer',
                'bread_added'    => 'required|numeric',
            ]);

            $branchId = $validated['branchId'];
            $productId = $validated['product_id'];
            $breadAdded = $validated['bread_added'];

            // Find the product based on product_id and branch_id
            $product = BranchProduct::where('product_id', $productId)
                ->where('branches_id', $branchId)
                ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found for this branch.',
                ], 404);
            }

            // Add the received bread to the total_quantity
            $product->total_quantity += $breadAdded;
            $product->save();

            // Optionally, update status of the report or breadAdded record
            BreadAdded::where('id', $validated['report_id'])
                ->update(['status' => $validated['status']]);

            return response()->json([
                'success' => true,
                'message' => 'Bread received and product quantity updated successfully.',
                'product' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to receive bread. ' . $e->getMessage(),
            ], 500);
        }
    }


    // public function receivedBreadBranchProduct(Request $request)
    // {
    //     $validatedData = $request->validate([
    //         'status' => 'required|string',
    //         'branchId' => 'required|integer',
    //         'report_id' => 'required|integer',
    //         'product_id' => 'required|integer',
    //         'bread_added' => 'required|numeric',
    //     ]);

    //     $branchId = $validatedData['branchId'];
    //     $productId = $validatedData['product_id'];
    //     $breadAdded = $validatedData['bread_added'];

    //     // Find the product based on product_id and branch_id
    //     $product = BranchProduct::where('product_id', $productId)
    //         ->where('branches_id', $branchId)
    //         ->first();
    //     if (!$product) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Product not found in the specified branch.',
    //         ], 404);
    //     }
    //      // Add the received bread to the total_quantity
    //      $product->total_quantity += $breadAdded;
    //      $product->save();

    //      // Optionally, update status of the report or breadAdded record
    //      BreadAdded::where('id', $validatedData['report_id'])
    //          ->update(['status' => $validatedData['status']]);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Bread successfully received and stock updated.',
    //         'product' => $product,
    //     ]);
    // }

    public function getSentBreadBranchProduct(Request $request, $branchId)
    {

            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 5);


            $sentBreadProducts = BreadAdded::with(['employee', 'product', 'fromBranch', 'toBranch'])
                ->where(function ($query) use ($branchId) {
                    $query->where('from_branch_id', $branchId)
                        ->orWhere('to_branch_id', $branchId);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            if ($perPage == 0) {
                return response()->json([
                    'data'           => $sentBreadProducts,
                    'total'          => count($sentBreadProducts),
                    'per_page'       => count($sentBreadProducts),
                    'current_page'   => 1,
                    'last_page'      => 1
                ]);
            } else {
                $paginate = new LengthAwarePaginator(
                    $sentBreadProducts->forPage($page, $perPage)->values(),
                    $sentBreadProducts->count(),
                    $perPage,
                    $page,
                    ['path' => url()->current()]

                );
            }
            return response()->json($paginate);

    }


    // public function getSentBreadBranchProduct(Request $request, $branchId)
    // {
    //     try {
    //         $perPage = $request->get('per_page', 5);

    //         $sentBreadProducts = BreadAdded::with(['employee', 'product', 'fromBranch', 'toBranch'])
    //             ->where('from_branch_id', $branchId)
    //             ->orderBy('created_at', 'desc') // Order by the creation date
    //             ->paginate($perPage);
    //         return response()->json($sentBreadProducts);
    //     } catch (\Exception $e) {
    //         // Handle and return an error response
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to fetch reports. ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'employee_id'            => 'required|exists:employees,id',
            'from_branch_id'         => 'required|exists:branches,id',
            'to_branch_id'           => 'required|exists:branches,id',
            'status'                 => 'required|string',
            'remark'                 => 'nullable|string',
            'products'               => 'required|array',
            'products.*.product_id'  => 'required|exists:products,id',
            'products.*.quantity'    => 'required|numeric|min:1',
            'products.*.price'       => 'required|numeric|min:1',
        ]);

        try {
            DB::beginTransaction();

            foreach ($validatedData['products'] as $product) {
                BreadAdded::create([
                    'employee_id'        => $validatedData['employee_id'],
                    'product_id'         => $product['product_id'],
                    'from_branch_id'     => $validatedData['from_branch_id'],
                    'to_branch_id'       => $validatedData['to_branch_id'],
                    'price'              => $product['price'],
                    'bread_added'        => $product['quantity'], // This is total quantity, store it once
                    'status'             => $validatedData['status'],
                    'remark'             => $validatedData['remark'] ?? null,
                ]);

                // 2. Update bread stock from the source branch
                $branchProduct = BranchProduct::where('branches_id', $validatedData['from_branch_id'])
                    ->where('product_id', $product['product_id'])
                    ->first();

                if(!$branchProduct) {
                    throw new \Exception('Bread stock not found for branch ID' .$validatedData['from_branch_id'] . ' and product ID' .$product['product_id']);
                }

                //Check if there's enough stock
                if ($branchProduct -> total_quantity < $product['quantity']) {
                    throw new \Exception('Insufficient bread stock for product ID ' . $product['product_id'] . ' in branch ID ' . $validatedData['from_branch_id']);
                }

                // Deduct the bread quantity
                $branchProduct->total_quantity -= $product['quantity'];
                $branchProduct->save();

            }

            DB::commit();

            return response()->json([
                'message' => 'Bread successfully transferred to another branch.',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to transfer bread.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(BreadAdded $breadAdded)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BreadAdded $breadAdded)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BreadAdded $breadAdded)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BreadAdded $breadAdded)
    {
        //
    }
}
