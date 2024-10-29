<?php

namespace App\Http\Controllers;

use App\Models\CashAdvance;
use Illuminate\Http\Request;

class CashAdvanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cashAdvance = CashAdvance::orderBy('created_at', 'desc')->with('employee')->take(7)->get();

        return response()->json($cashAdvance, 200);
    }

    /**
     * Store a resource in storage.
     */

    public function searchCashAdvance(Request $request)
    {
        $keyword = $request->input('keyword');

        $cashAdvances = CashAdvance::with('employee')
        ->when($keyword !== null, function ($query) use ($keyword) {
            $query->whereHas('employee', function($q) use ($keyword) {
                $q->where('firstname', 'LIKE', '%' . $keyword . '%')
                ->orWhere('middlename', 'LIKE', '%' . $keyword . '%')
                ->orWhere('lastname', 'LIKE', '%' . $keyword . '%');
            });
        }, function ($query) {
            $query->orderBy('created_at', 'desc');
        })
        ->take(7)
        ->get();

        return response()->json($cashAdvances);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric',
            'reason' => 'required|string'
        ]);

        $cashAdvance = CashAdvance::create($validatedData);

        return response()->json($cashAdvance,201);
    }

    /**
     * Display the specified resource.
     */
    public function show(CashAdvance $cashAdvance)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CashAdvance $cashAdvance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CashAdvance $cashAdvance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CashAdvance $cashAdvance)
    {
        //
    }
}
