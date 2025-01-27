<?php

namespace App\Http\Controllers;

use App\Models\SoftdrinksSalesReport;
use Illuminate\Http\Request;

class SoftdrinksSalesReportController extends Controller
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

        $softdrinksSalesReport = SoftdrinksSalesReport::findorFail($id);
        $softdrinksSalesReport->price = $validatedData['price'];
        $softdrinksSalesReport->save();

        return response()->json(['message' => 'Price updated successfully', 'price' => $softdrinksSalesReport]);
    }
    public function updatedBeginnings(Request $request, $id)
    {
        $validatedData = $request->validate([
            'beginnings' => 'required|integer'
        ]);

        $softdrinksSalesReport = SoftdrinksSalesReport::findorFail($id);
        $softdrinksSalesReport->beginnings = $validatedData['beginnings'];
        $softdrinksSalesReport->save();

        return response()->json(['message' => 'beginnings updated successfully', 'beginnings' => $softdrinksSalesReport]);
    }
    public function updatedRemaining(Request $request, $id)
    {
        $validatedData = $request->validate([
            'remaining' => 'required|integer'
        ]);

        $softdrinksSalesReport = SoftdrinksSalesReport::findorFail($id);
        $softdrinksSalesReport->remaining = $validatedData['remaining'];
        $softdrinksSalesReport->save();

        return response()->json(['message' => 'remaining updated successfully', 'remaining' => $softdrinksSalesReport]);
    }
    public function updatedSoftdrinksOut(Request $request, $id)
    {
        $validatedData = $request->validate([
            'out' => 'required|integer'
        ]);

        $softdrinksSalesReport = SoftdrinksSalesReport::findorFail($id);
        $softdrinksSalesReport->out = $validatedData['out'];
        $softdrinksSalesReport->save();

        return response()->json(['message' => 'out updated successfully', 'out' => $softdrinksSalesReport]);
    }
    public function updatedAddedStocks(Request $request, $id)
    {
        $validatedData = $request->validate([
            'added_stocks' => 'required|integer'
        ]);

        $softdrinksSalesReport = SoftdrinksSalesReport::findorFail($id);
        $softdrinksSalesReport->added_stocks = $validatedData['added_stocks'];
        $softdrinksSalesReport->save();

        return response()->json(['message' => 'added_stocks updated successfully', 'added_stocks' => $softdrinksSalesReport]);
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
     * @param  \App\Models\SoftdrinksSalesReport  $softdrinksSalesReport
     * @return \Illuminate\Http\Response
     */
    public function show(SoftdrinksSalesReport $softdrinksSalesReport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SoftdrinksSalesReport  $softdrinksSalesReport
     * @return \Illuminate\Http\Response
     */
    public function edit(SoftdrinksSalesReport $softdrinksSalesReport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SoftdrinksSalesReport  $softdrinksSalesReport
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SoftdrinksSalesReport $softdrinksSalesReport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SoftdrinksSalesReport  $softdrinksSalesReport
     * @return \Illuminate\Http\Response
     */
    public function destroy(SoftdrinksSalesReport $softdrinksSalesReport)
    {
        //
    }
}
