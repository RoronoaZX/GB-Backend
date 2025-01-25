<?php

namespace App\Http\Controllers;

use App\Models\BreadSalesReport;
use Illuminate\Http\Request;

class BreadSalesReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
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

        $breadSalesReport = BreadSalesReport::findorFail($id);
        $breadSalesReport->price = $validatedData['price'];
        $breadSalesReport->save();

        return response()->json(['message' => 'Price updated successfully', 'price' => $breadSalesReport]);
    }
    public function updateBeginnings(Request $request, $id)
    {
        $validatedData = $request->validate([
            'beginnings' => 'required|integer'
        ]);

        $breadSalesReport = BreadSalesReport::findorFail($id);
        $breadSalesReport->beginnings = $validatedData['beginnings'];
        $breadSalesReport->save();

        return response()->json(['message' => 'Beginnings updated successfully', 'beginnings' => $breadSalesReport]);
    }
    public function updateRemaining(Request $request, $id)
    {
        $validatedData = $request->validate([
            'remaining' => 'required|integer'
        ]);

        $breadSalesReport = BreadSalesReport::findorFail($id);
        $breadSalesReport->remaining = $validatedData['remaining'];
        $breadSalesReport->save();

        return response()->json(['message' => 'Remaining updated successfully', 'remaining' => $breadSalesReport]);
    }
    public function updateBreadOut(Request $request, $id)
    {
        $validatedData = $request->validate([
            'bread_out' => 'required|integer'
        ]);

        $breadSalesReport = BreadSalesReport::findorFail($id);
        $breadSalesReport->bread_out = $validatedData['bread_out'];
        $breadSalesReport->save();

        return response()->json(['message' => 'Bread out updated successfully', 'bread_out' => $breadSalesReport]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BreadSalesReport  $breadSalesReport
     * @return \Illuminate\Http\Response
     */
    public function show(BreadSalesReport $breadSalesReport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BreadSalesReport  $breadSalesReport
     * @return \Illuminate\Http\Response
     */
    public function edit(BreadSalesReport $breadSalesReport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BreadSalesReport  $breadSalesReport
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BreadSalesReport $breadSalesReport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BreadSalesReport  $breadSalesReport
     * @return \Illuminate\Http\Response
     */
    public function destroy(BreadSalesReport $breadSalesReport)
    {
        //
    }
}
