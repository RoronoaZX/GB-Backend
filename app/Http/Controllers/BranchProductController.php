<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BranchProductController extends Controller
{
    public function index()
    {
        //
    }

    public function getProducts($branchId)
    {
        $branchProducts = BranchProduct::orderBy('created_at', 'desc')->where('branches_id', $branchId)->with(['branch', 'product'])->get();
        return response()->json($branchProducts, 200);
    }




    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'branches_id' => 'required|exists:branches,id',
            'product_id' => 'required|exists:products,id',
            'category' => 'required|string|max:255',
            'price' => 'required|numeric',
            'beginnings' => 'required|numeric',
            'total_quantity' => 'required|numeric',
        ]);

        $existingBranchProduct = BranchProduct::where('branches_id', $validatedData['branches_id'])->where('product_id', $validatedData['product_id'])->first();

        if ($existingBranchProduct) {
            return response()->json([
                'message' => 'The product already exists in this branch.'
            ]);
        }

        $branchProduct = BranchProduct::create([
            'branches_id' => $validatedData['branches_id'],
            'product_id' =>$validatedData['product_id'],
            'category' =>$validatedData['category'],
            'price' => $validatedData['price'],
            'beginnings' => $validatedData['beginnings'],
            'total_quantity' => $validatedData['total_quantity']
        ]);

        return response()->json([
            'message' => "Branch product saved successfully",
            'data' => $branchProduct
        ], 201);
    }

    public function show(BranchProduct $branchesProduct)
    {
        //
    }

    public function updatePrice(Request $request, $id)
    {
        $validatedData = $request->validate([
            'price' => 'required|integer',
        ]);

        $branchProduct = BranchProduct::findorFail($id);
        $branchProduct->price = $validatedData['price'];
        $branchProduct->save();

        return response()->json(['message' => 'Price updated successfully', 'price' => $branchProduct]);
    }

    public function updateTotatQuatity(Request $request, $id)
    {
        $validatedData = $request->validate([
            'total_quantity' => 'required|integer'
        ]);
        $branchProduct = BranchProduct::findOrFail($id);
        $branchProduct->total_quantity = $validatedData['total_quantity'];
        $branchProduct->save();

        return response()->json(['message' => 'Total Quantity updated successfully', 'total quantity' => $branchProduct]);
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
    $branchId = $request->input('branch_id');
    $keyword = $request->input('keyword');

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
