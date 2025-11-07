<?php

namespace App\Http\Controllers;

use App\Models\OtherProducts;
use Illuminate\Http\Request;

class OtherProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function updatePrice(Request $request, $id)
    {
        $validatedData = $request->validate([
            'price' => 'required|integer'
        ]);

        $otherProducts           = OtherProducts::findorFail($id);
        $otherProducts->price    = $validatedData['price'];
        $otherProducts->save();

        return response()->json([
            'message' => 'Price updated successfully',
            'price' => $otherProducts
        ]);
    }
    public function updatedBeginnings(Request $request, $id)
    {
        $validatedData = $request->validate([
            'beginnings' => 'required|integer'
        ]);

        $otherProducts               = OtherProducts::findorFail($id);
        $otherProducts->beginnings   = $validatedData['beginnings'];
        $otherProducts->save();

        return response()->json(['message' => 'beginnings updated successfully', 'beginnings' => $otherProducts]);
    }
    public function updatedRemaining(Request $request, $id)
    {
        $validatedData = $request->validate([
            'remaining' => 'required|integer'
        ]);

        $otherProducts               = OtherProducts::findorFail($id);
        $otherProducts->remaining    = $validatedData['remaining'];
        $otherProducts->save();

        return response()->json([
            'message' => 'remaining updated successfully',
            'remaining' => $otherProducts
        ]);
    }
    public function updatedOtherProductsOut(Request $request, $id)
    {
        $validatedData = $request->validate([
            'out' => 'required|integer'
        ]);

        $otherProducts       = OtherProducts::findorFail($id);
        $otherProducts->out  = $validatedData['out'];
        $otherProducts->save();

        return response()->json([
            'message' => 'out updated successfully',
            'out' => $otherProducts
        ]);
    }
    public function updatedAddedStocks(Request $request, $id)
    {
        $validatedData = $request->validate([
            'added_stocks' => 'required|integer'
        ]);

        $otherProducts                   = OtherProducts::findorFail($id);
        $otherProducts->added_stocks     = $validatedData['added_stocks'];
        $otherProducts->save();

        return response()->json([
            'message' => 'added_stocks updated successfully',
            'added_stocks' => $otherProducts
        ]);
    }

    public function addingOtherProduction(Request $request)
    {
        $validated = $request->validate([
            'user_id'            => 'required|exists:users,id',
            'branch_id'          => 'required|exists:branches,id',
            'sales_report_id'    => 'required|exists:sales_reports,id',
            'product_id'         => 'required|exists:products,id',
            'product_name'       => 'required|string',
            'price'              => 'required|numeric',
            'beginnings'         => 'numeric',
            'remaining'          => 'numeric',
            'added_stocks'       => 'numeric',
            'out'                => 'numeric',
            'sold'               => 'numeric',
            'total'              => 'numeric',
            'sales'              => 'numeric',
        ]);

        $selectaSalesReport = OtherProducts::create($validated);

        return response()->json([
            'message' => 'Other Production added successfully',
            'selectaSalesReport' => $selectaSalesReport
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(OtherProducts $otherProducts)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OtherProducts $otherProducts)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OtherProducts $otherProducts)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OtherProducts $otherProducts)
    {
        //
    }
}
