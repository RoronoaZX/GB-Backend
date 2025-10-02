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
    public function updateExpensesReport(Request $request)
    {
        $validatedData = $request->validate([
            'sales_report_id'            => 'required|integer|exists:sales_reports,id',
            'branch_id'                  => 'required|integer|exists:branches,id',
            'user_id'                    => 'required|integer|exists:users,id',
            'expenses'                   => 'required|array',
            'expenses.*.name'            => 'required|string|max:255',
            'expenses.*.amount'          => 'required|numeric|min:0',
            'expenses.*.description'     => 'nullable|string|max:500',
            'expenses.*.category'        => 'nullable|string|max:255',
        ]);

        try {
            foreach ($validatedData['expenses'] as $expense) {
                ExpencesReport::create([
                    'sales_report_id'    => $validatedData['sales_report_id'],
                    'branch_id'          => $validatedData['branch_id'],
                    'user_id'            => $validatedData['user_id'],
                    'name'               => $expense['name'],
                    'amount'             => $expense['amount'],
                    'description'        => $expense['description'] ?? null,
                    'category'           => $expense['category'] ?? null,
                ]);
            }

            return response()->json([
                'message' => 'Expenses successfully stored!',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error'      => 'Something went wrong!',
                'details'    => $e->getMessage(),
            ], 500);
        }
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
