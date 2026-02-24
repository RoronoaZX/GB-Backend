<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\BreadAdded;
use App\Models\HistoryLog;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;



class BranchProductController extends Controller
{

    public function samplePaginationFretchingBranchProducts(Request $request)
    {
        $perPage         = $request->query('rowsPerPage', 5);
        $branchId        = $request->query('branchId');

        $query           = BranchProduct::orderBy('created_at', 'desc')
                                ->where('branches_id', $branchId)
                                ->with(['branch', 'product']);
        $branchProduct   = $query->paginate($perPage);

        return response()->json([
            'data'  => $branchProduct,
            'total' => $branchProduct->total()
        ]);
    }

    public function getProducts($branchId)
    {
        $branchProducts = BranchProduct::orderBy('created_at', 'desc')
                                ->where('branches_id', $branchId)
                                ->with(['branch', 'product'])
                                ->get();
        return response()->json($branchProducts, 200);
    }

    public function fetchBranchBreadProducts(Request $request)
    {
        $validated = $request->validate([
            'branches_id' => 'required|integer',
            'category'    => 'nullable|string',
        ]);


        $products = BranchProduct::where('branches_id', $validated['branches_id'])
                        ->when($validated['category'], function ($query, $category) {
                            $query->where('category', $category);
                        })
                        ->with('product') // Load the product relationship
                        ->get()
                        ->map(function ($branchProduct) {
                            $product = $branchProduct->product;
                            if ($product) {
                                $product->price = $branchProduct->price; // Add price to the product object
                            }
                            return $product;
                        });

        return response()->json($products);
    }

    public function fetchBranchSelectaProducts(Request $request)
    {
        $validated = $request->validate([
            'branches_id' => 'required|integer',
            'category'    => 'nullable|string',
        ]);


        $products = BranchProduct::where('branches_id', $validated['branches_id'])
                    ->when($validated['category'], function ($query, $category) {
                        $query->where('category', $category);
                    })
                    ->with('product') // Load the product relationship
                    ->get()
                    ->map(function ($branchProduct) {
                        $product = $branchProduct->product;
                        if ($product) {
                            $product->price = $branchProduct->price; // Add price to the product object
                        }
                        return $product;
                    });

        return response()->json($products);
    }

    public function fetchBranchSoftdrinksProducts(Request $request)
    {
        $validated = $request->validate([
            'branches_id' => 'required|integer',
            'category'    => 'nullable|string',
        ]);


        $products = BranchProduct::where('branches_id', $validated['branches_id'])
                        ->when($validated['category'], function ($query, $category) {
                            $query->where('category', $category);
                        })
                        ->with('product') // Load the product relationship
                        ->get()
                        ->map(function ($branchProduct) {
                            $product = $branchProduct->product;
                            if ($product) {
                                $product->price = $branchProduct->price; // Add price to the product object
                            }
                            return $product;
                        });

        return response()->json($products);
    }

    public function fetchBranchOtherProducts(Request $request)
    {
        $validated = $request->validate([
            'branches_id' => 'required|integer',
            'category'    => 'nullable|string',
        ]);

        $products = BranchProduct::where('branches_id', $validated['branches_id'])
                        ->when($validated['category'], function ($query, $category) {
                            $query->where('category', $category);
                        })
                        ->with('product')
                        ->get()
                        ->map(function ($branchProduct) {
                            $product = $branchProduct->product;
                            if ($product) {
                                $product->price = $branchProduct->price;
                            }
                            return $product;
                        });
        return response()->json($products);
    }

    public function searchBranchProducts(Request $request)
    {
        $validated = $request->validate([
            'branches_id' => 'required|integer',
            'query'       => 'nullable|string',
            'category'    => 'nullable|string',
        ]);

        $products = BranchProduct::where('branches_id', $validated['branches_id'])
                        ->when($validated['category'], function ($query, $category) {
                            $query->where('category', $category);
                        })
                        ->when($validated['query'], function ($query, $search) {
                            $query->whereHas('product', function ($subQuery) use ($search) {
                                $subQuery->where('name', 'like', "%{$search}%");
                            });
                        })
                        ->with(['product', 'branch'])
                        ->get();

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'branches_id'       => 'required|exists:branches,id',
            'product_id'        => 'required|exists:products,id',
            'category'          => 'required|string|max:255',
            'price'             => 'required|numeric',
            'beginnings'        => 'required|numeric',
            'total_quantity'    => 'required|numeric',
        ]);

        $existingBranchProduct = BranchProduct::where('branches_id', $validatedData['branches_id'])
                                    ->where('product_id', $validatedData['product_id'])
                                    ->first();

        if ($existingBranchProduct) {
            return response()->json([
                'message' => 'The product already exists in this branch.'
            ]);
        }

        $branchProduct = BranchProduct::create([
            'branches_id'        => $validatedData['branches_id'],
            'product_id'         => $validatedData['product_id'],
            'category'           => $validatedData['category'],
            'price'              => $validatedData['price'],
            'beginnings'         => $validatedData['beginnings'],
            'total_quantity'     => $validatedData['total_quantity']
        ]);

        return response()->json([
            'message' => "Branch product saved successfully",
            'data'    => $branchProduct
        ], 201);
    }

    public function updateProduct(Request $request)
    {
        $validatedData = $request->validate([
            'id' => 'required|integer',
            'updated_data' => 'required|numeric',
            'updated_field' => 'required|string',
        ]);

        $branchProduct = BranchProduct::find($validatedData['id']);

        if (!$branchProduct) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found.'
            ], 404);
        }

        // 🔐 Allowed fields with readable labels
        $allowedFields = [
            'price' => 'Price',
            'beginnings' => 'Beginning Stock',
            'new_production' => 'New Production',
            'total_quantity' => 'Total Quantity'
        ];

        $field = $validatedData['updated_field'];

        if (!array_key_exists($field, $allowedFields)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Field not allowed to update.'
            ], 403);
        }

        $value = $validatedData['updated_data'];
        $oldValue = $branchProduct->$field;

        // 🔄 If unchanged
        if ($oldValue == $value) {
            return response()->json([
                'status' => 'warning',
                'message' => $allowedFields[$field] . ' unchanged.',
                'updated_field' => $field,
                'data' => $branchProduct
            ], 200);
        }

        // ✅ Update field
        $branchProduct->$field = $value;
        $branchProduct->save();

        return response()->json([
            'status' => 'success',
            'message' => $allowedFields[$field] . ' updated successfully.',
            'updated_field' => $field,
            'old_value' => $oldValue,
            'new_value' => $value,
            'data' => $branchProduct
        ], 200);
    }

    // public function updateProduct(Request $request)
    // {
    //     $validatedData = $request->validate([
    //         'id' => 'required|integer',
    //         'updated_data' => 'required|numeric',
    //         'updated_field' => 'required|string',
    //     ]);

    //     $branchProduct = BranchProduct::findOrFail($validatedData['id']);

    //     if (!$branchProduct) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Product not found.'
    //         ], 404);
    //     }

    //     // 🔐 Whitelist allowed fields
    //     $allowedFields = [
    //         'price',
    //         'beginnings',
    //         'new_production',
    //         'total_quantity'
    //     ];

    //     if (!in_array($validatedData['updated_field'], $allowedFields)) {
    //         return response()->json([
    //             'message' => 'Field not allowed to update.'
    //         ], 403);
    //     }

    //     $field = $validatedData['updated_field'];
    //     $value = $validatedData['updated_data'];

    //     $oldValue = $branchProduct->$field;

    //     // Optional: prevent unnecessary update
    //     if ($oldValue == $value) {
    //         return response()->json([
    //             'status' => 'warning',
    //             'message' => $allowedFields[$field] . ' unchanged.',
    //             'updated_field' => $field,
    //             'data' => $branchProduct
    //         ], 200);
    //     }

    //     // ✅ Dynamic update
    //     $branchProduct->$field = $value;
    //     $branchProduct->save();

    //     // 🔥 Make field name readable
    //     $readableField = ucfirst(str_replace('_', ' ', $field));

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => $allowedFields[$field] . ' updated successfully.',
    //         'updated_field' => $field,
    //         'old_value' => $oldValue,
    //         'new_value' => $value,
    //         'data' => $branchProduct
    //     ], 200);
    // }

    public function updatePrice(Request $request, $id)
    {
        $validatedData = $request->validate([
            'price' => 'required|integer',
        ]);

        $branchProduct          = BranchProduct::findorFail($id);
        $branchProduct->price   = $validatedData['price'];
        $branchProduct->save();

        //Save to history log

        HistoryLog::create([
            'report_id'          => $request->input('report_id'),
            'name'               => $request->input('name'),
            'original_data'      => $request->input('original_data'),
            'updated_data'       => $request->input('updated_data'),
            'updated_field'      => $request->input('updated_field'),
            'designation'        => $request->input('designation'),
            'designation_type'   => $request->input('designation_type'),
            'action'             => $request->input('action'),
            'type_of_report'     => $request->input('type_of_report'),
            'user_id'            => $request->input('user_id'),
        ]);

        return response()->json(['message' => 'Price updated successfully', 'price' => $branchProduct]);
    }

    public function updateTotatQuatity(Request $request, $id)
    {
        $validatedData = $request->validate([
            'total_quantity' => 'required|integer'
        ]);
        $branchProduct                   = BranchProduct::findOrFail($id);
        $branchProduct->total_quantity   = $validatedData['total_quantity'];
        $branchProduct->save();

        HistoryLog::create([
            'report_id'          => $request->input('report_id'),
            'name'               => $request->input('name'),
            'original_data'      => $request->input('original_data'),
            'updated_data'       => $request->input('updated_data'),
            'updated_field'      => $request->input('updated_field'),
            'designation'        => $request->input('designation'),
            'designation_type'   => $request->input('designation_type'),
            'action'             => $request->input('action'),
            'type_of_report'     => $request->input('type_of_report'),
            'user_id'            => $request->input('user_id'),
        ]);

        return response()->json([
            'message' => 'Total Quantity updated successfully',
            'total quantity' => $branchProduct
        ]);
    }

    public function updateNewProduction(Request $request, $id)
    {
        $validatedData = $request->validate([
            'new_production' => 'required|integer'
        ]);

        $branchProduct                   = BranchProduct::findOrFail($id);
        $branchProduct->new_production   = $validatedData['new_production'];
        $branchProduct->save();

        HistoryLog::create([
            'report_id'          => $request->input('report_id'),
            'name'               => $request->input('name'),
            'original_data'      => $request->input('original_data'),
            'updated_data'       => $request->input('updated_data'),
            'updated_field'      => $request->input('updated_field'),
            'designation'        => $request->input('designation'),
            'designation_type'   => $request->input('designation_type'),
            'action'             => $request->input('action'),
            'type_of_report'     => $request->input('type_of_report'),
            'user_id'            => $request->input('user_id'),
        ]);

        return response()->json([
            'message' => 'New Production updated successfully',
            'new production' => $branchProduct
        ]);
    }

    public function updateBeginnings(Request $request, $id)
    {
        $validatedData = $request->validate([
            'beginnings' => 'required|integer'
        ]);
        $branchProduct               = BranchProduct::findOrFail($id);
        $branchProduct->beginnings   = $validatedData['beginnings'];
        $branchProduct->save();

        HistoryLog::create([
            'report_id'          => $request->input('report_id'),
            'name'               => $request->input('name'),
            'original_data'      => $request->input('original_data'),
            'updated_data'       => $request->input('updated_data'),
            'updated_field'      => $request->input('updated_field'),
            'designation'        => $request->input('designation'),
            'designation_type'   => $request->input('designation_type'),
            'action'             => $request->input('action'),
            'type_of_report'     => $request->input('type_of_report'),
            'user_id'            => $request->input('user_id'),
        ]);

        return response()->json([
            'message' => 'Total Quantity updated successfully',
            'total quantity' => $branchProduct
        ]);
    }

    public function destroy($id)
    {
        $branchProduct = BranchProduct::find($id);

        if (!$branchProduct) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }

        $branchProduct->delete();
        return response()->json([
            'message' => 'Product deleted successfully'
        ]);

    }

    public function searchProducts(Request $request)
    {
        $branchId    = $request->input('branch_id');
        $keyword     = $request->input('keyword');

        Log::info('Search request received', ['branch_id' => $branchId, 'keyword' => $keyword]);

        // Search for products with a join on branch_products to filter by branch_id and keyword
        $products = Product::with(['branch_products' => function ($query) use ($branchId) {
                            $query->where('branches_id', $branchId);
                        }])
                        ->where('products.name', 'like', '%' . $keyword . '%')
                        ->select('products.*', 'branch_products.price')
                        ->join('branch_products', 'products.id', '=', 'branch_products.product_id')
                        ->where('branch_products.branches_id', $branchId)
                        ->get();

        Log::info('Search results', ['products' => $products]);

        return response()->json($products);
    }
}
