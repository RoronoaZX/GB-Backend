<?php

namespace App\Http\Controllers;

use App\Models\ExpencesReport;
use Illuminate\Http\Request;

class ExpencesReportController extends Controller
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

    public function updateName(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255'

        ]);

        $expensesReport = ExpencesReport::findorFail($id);
        $expensesReport->name = $validatedData['name'];
        $expensesReport->save();

        return response()->json(['message' => 'name updated successfully', 'name' => $expensesReport]);
    }
    public function updateDescription(Request $request, $id)
    {
        $validatedData = $request->validate([
            'description' => 'required|string|max:255'

        ]);

        $expensesReport = ExpencesReport::findorFail($id);
        $expensesReport->description = $validatedData['description'];
        $expensesReport->save();

        return response()->json(['message' => 'description updated successfully', 'description' => $expensesReport]);
    }
    public function updateAmount(Request $request, $id)
    {
        $validatedData = $request->validate([
            'amount' => 'required|numeric'
        ]);

        $expensesReport = ExpencesReport::findorFail($id);
        $expensesReport->amount = $validatedData['amount'];
        $expensesReport->save();

        return response()->json(['message' => 'amount updated successfully', 'amount' => $expensesReport]);
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
     * @param  \App\Models\ExpencesReport  $expencesReport
     * @return \Illuminate\Http\Response
     */
    public function show(ExpencesReport $expencesReport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ExpencesReport  $expencesReport
     * @return \Illuminate\Http\Response
     */
    public function edit(ExpencesReport $expencesReport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ExpencesReport  $expencesReport
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ExpencesReport $expencesReport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ExpencesReport  $expencesReport
     * @return \Illuminate\Http\Response
     */
    public function destroy(ExpencesReport $expencesReport)
    {
        //
    }
}
