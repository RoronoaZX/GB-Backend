<?php

namespace App\Http\Controllers;

use App\Models\HistoryLog;
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

        $selectaSalesReport          = SelectaSalesReport::findorFail($id);
        $selectaSalesReport->price   = $validatedData['price'];
        $selectaSalesReport->save();

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
            'user_id'            => $request->input('user_id')
        ]);

        return response()->json([
            'message' => 'Price updated successfully',
            'price' => $selectaSalesReport
        ]);
    }
    public function updatedBeginnings(Request $request, $id)
    {
        $validatedData = $request->validate([
            'beginnings' => 'required|integer'
        ]);

        $selectaSalesReport              = SelectaSalesReport::findorFail($id);
        $selectaSalesReport->beginnings  = $validatedData['beginnings'];
        $selectaSalesReport->save();

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
            'message'        => 'beginnings updated successfully',
            'beginnings'     => $selectaSalesReport
        ]);
    }
    public function updatedRemaining(Request $request, $id)
    {
        $validatedData = $request->validate([
            'remaining' => 'required|integer'
        ]);

        $selectaSalesReport              = SelectaSalesReport::findorFail($id);
        $selectaSalesReport->remaining   = $validatedData['remaining'];
        $selectaSalesReport->save();

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
            'message' => 'remaining updated successfully',
            'remaining' => $selectaSalesReport
        ]);
    }
    public function updatedSelectaOut(Request $request, $id)
    {
        $validatedData = $request->validate([
            'out' => 'required|integer'
        ]);

        $selectaSalesReport          = SelectaSalesReport::findorFail($id);
        $selectaSalesReport->out     = $validatedData['out'];
        $selectaSalesReport->save();

        return response()->json([
            'message' => 'out updated successfully',
            'out' => $selectaSalesReport
        ]);
    }
    public function updatedAddedStocks(Request $request, $id)
    {
        $validatedData = $request->validate([
            'added_stocks' => 'required|integer'
        ]);

        $selectaSalesReport                  = SelectaSalesReport::findorFail($id);
        $selectaSalesReport->added_stocks    = $validatedData['added_stocks'];
        $selectaSalesReport->save();

        return response()->json([
            'message' => 'added_stocks updated successfully',
            'added_stocks' => $selectaSalesReport
        ]);
    }

    public function addingSelectaProduction(Request $request)
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

        $selectaSalesReport = SelectaSalesReport::create($validated);

        return response()->json([
            'message' => 'Selecta Production added successfully',
            'selectaSalesReport' => $selectaSalesReport
        ]);
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
