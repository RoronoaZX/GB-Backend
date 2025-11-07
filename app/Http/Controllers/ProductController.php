<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Dotenv\Repository\RepositoryInterface;
use Illuminate\Http\Request;

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

       $validatedData = $request->validate([
        'name'       => 'required|string|unique:products',
        'category'   => 'required|string',
         ]);


       $product->update($validatedData);
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
        $product->delete();
        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    public function fetchBreadProducts()
    {
        $breadProducts = Product::where('category', 'bread')->get();
        return response()->json($breadProducts);
    }
}
