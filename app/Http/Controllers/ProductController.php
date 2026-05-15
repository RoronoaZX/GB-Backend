<?php

namespace App\Http\Controllers;

use App\Models\BranchProduct;
use App\Models\Product;
use Dotenv\Repository\RepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\HistoryLogService;


class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::orderBy('created_at', 'desc')->get();
        return $products;
    }

    public function searchProducts(Request $request)
    {
        $keyword = $request->input('keyword');

        $request->validate([
            'keyword' => 'required|string|max:255'
        ]);

        $result = Product::search($keyword)->get();

        return response()->json($result);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name'       => 'required|string|unique:products',
            'category'   => 'required|string',
        ]);

        $product = Product::create([
            'name'       => $validatedData['name'],
            'category'   => $validatedData['category']
        ]);

        $productResponseData =  $product->fresh();

        // LOG-10 — Product: Create
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'type_of_report'   => 'Product',
            'name'             => $product->name,
            'action'           => 'created',
            'updated_data'     => $product->toArray(),
            'designation'      => 0,
            'designation_type' => 'system',
        ]);

        return response()->json([
            'message'    => "Product saved successfully",
            $productResponseData
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return response()->json([
            'data'       => ['sample']
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        //
    }


    public function updateProducts(Request $request)
    {
        $validated = $request->validate([
            'id'     => 'required|integer|exists:products,id',
            'field'  => 'required|string|in:name,category',
            'value'  => 'required|string',
        ]);

        $product = Product::findOrFail($validated['id']);
        $oldValue = $product->{$validated['field']};

        // Dynamic update
        $product->{$validated['field']} = $validated['value'];
        $product->save();

        // LOG-11 — Product: Update (Dynamic)
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'type_of_report'   => 'Product',
            'name'             => $product->name,
            'action'           => 'updated',
            'updated_field'    => $validated['field'],
            'original_data'    => $oldValue,
            'updated_data'     => $validated['value'],
            'designation'      => 0,
            'designation_type' => 'system',
        ]);

        // ✅ If category changed, update BranchProduct category too
        if ($validated['field'] === 'category') {
            BranchProduct::where('product_id', $product->id)
                ->update([
                    'category' => $validated['value']
                ]);
        }

        return response()->json([
            'message'    => 'Product updated successfully',
            'data'       => $product
        ], 200);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
       $product = Product::find($id);

       if (!$product) {
        return response()->json([
            'message'        => 'Product not found'
        ], 404);
       }

       $oldData = $product->toArray();

       $validatedData = $request->validate([
        'name'       => 'required|string|unique:products,name,' . $id,
        'category'   => 'required|string',
         ]);


       $product->update($validatedData);

       // LOG-11 — Product: Update (Standard)
       HistoryLogService::log([
            'user_id'          => Auth::id(),
            'type_of_report'   => 'Product',
            'name'             => $product->name,
            'action'           => 'updated',
            'original_data'    => $oldData,
            'updated_data'     => $product->toArray(),
            'designation'      => 0,
            'designation_type' => 'system',
        ]);

       $updated_product = $product->fresh();
       return response()->json($updated_product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = Product::find($id);
        if(!$product) {
            return response()->json([
                'message' => 'Product not found'
            ], 404);
        }
        $oldData = $product->toArray();
        $product->delete();

        // LOG-12 — Product: Delete
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'type_of_report'   => 'Product',
            'name'             => $product->name,
            'action'           => 'deleted',
            'original_data'    => $oldData,
            'designation'      => 0,
            'designation_type' => 'system',
        ]);

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    public function fetchBreadProducts(Request $request)
    {
        // Accept an optional ?category param; default to 'bread' for backward compatibility.
        // strtolower() ensures "Bread", "bread", and "BREAD" all match correctly.
        $category      = strtolower($request->input('category', 'bread'));
        $breadProducts = Product::where('category', $category)->get();
        return response()->json($breadProducts);
    }
}
