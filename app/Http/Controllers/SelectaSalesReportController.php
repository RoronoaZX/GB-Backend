<?php

namespace App\Http\Controllers;

use App\Models\SelectaSalesReport;
use Illuminate\Http\Request;

class SelectaSalesReportController extends Controller
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

        $selectaSalesReport = SelectaSalesReport::findorFail($id);
        $selectaSalesReport->price = $validatedData['price'];
        $selectaSalesReport->save();

        return response()->json(['message' => 'Price updated successfully', 'price' => $selectaSalesReport]);
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
     * @param  \App\Models\SelectaSalesReport  $selectaSalesReport
     * @return \Illuminate\Http\Response
     */
    public function show(SelectaSalesReport $selectaSalesReport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SelectaSalesReport  $selectaSalesReport
     * @return \Illuminate\Http\Response
     */
    public function edit(SelectaSalesReport $selectaSalesReport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SelectaSalesReport  $selectaSalesReport
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SelectaSalesReport $selectaSalesReport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SelectaSalesReport  $selectaSalesReport
     * @return \Illuminate\Http\Response
     */
    public function destroy(SelectaSalesReport $selectaSalesReport)
    {
        //
    }
}
