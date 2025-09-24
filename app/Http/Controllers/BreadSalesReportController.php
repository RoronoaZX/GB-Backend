<?php

namespace App\Http\Controllers;

use App\Models\BreadSalesReport;
use App\Models\HistoryLog;
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

        HistoryLog::create([
            'report_id' => $request->input('report_id'),
            'name' => $request->input('name'),
            'original_data'      => $request->input('original_data'),
            'updated_data'       => $request->input('updated_data'),
            'updated_field'      => $request->input('updated_field'),
            'designation'        => $request->input('designation'),
            'designation_type'   => $request->input('designation_type'),
            'action'             => $request->input('action'),
            'type_of_report'     => $request->input('type_of_report'),
            'user_id'            => $request->input('user_id'),
        ]);

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

        HistoryLog::create([
            'report_id' => $request->input('report_id'),
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

        return response()->json(['message' => 'Beginnings updated successfully', 'beginnings' => $breadSalesReport]);
    }
    public function updatedNewProduction(Request $request, $id)
    {
        $validatedData = $request->validate([
            'new_production' => 'required|integer'
        ]);

        $breadSalesReport = BreadSalesReport::findorFail($id);
        $breadSalesReport->new_production = $validatedData['new_production'];
        $breadSalesReport->save();

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

        return response()->json(['message' => 'Beginnings updated successfully', 'new_production' => $breadSalesReport]);
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

        return response()->json(['message' => 'Bread out updated successfully', 'bread_out' => $breadSalesReport]);
    }

    public function addingBreadProduction(Request $request)
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
            'new_production'     => 'numeric',
            'bread_out'          => 'numeric',
            'bread_sold'         => 'numeric',
            'total'              => 'numeric',
            'sales'              => 'numeric',
        ]);

        $breadProduction = BreadSalesReport::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Bread production recorded successfully!',
            'data' => $breadProduction,
        ]);
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
