<?php

namespace App\Http\Controllers;

use App\Models\BirReport;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BirReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for fetching resource.
     */
    public function fetchBranchDataForReports($branchId)
    {
        $branchDataBirReports = Branch::where('id', $branchId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($branchDataBirReports);
    }

    public function fetchNonVATBirReports(Request $request,$branchId)
    {

        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        $birReports = BirReport::where('branch_id', $branchId)
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->with(['user', 'branch'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($report) {
                $report->category = 'Non-VAT';
                return $report;
            });

        return response()->json($birReports);
     // Get the current month and year
    // $currentMonth = Carbon::now()->month;
    // $currentYear = Carbon::now()->year;

    // // Fetch BIR reports filtered by month and year
    // $birReports = BirReport::where('branch_id', $branchId)
    //     ->whereMonth('created_at', $currentMonth)
    //     ->whereYear('created_at', $currentYear)
    //     ->with(['user', 'branch'])
    //     ->orderBy('created_at', 'desc')
    //     ->get()
    //     ->map(function ($report) {
    //         // Add a static or dynamic category field
    //         $report->category = 'Non-VAT';  // You can customize this logic based on your data
    //         return $report;
    //     });

    // return response()->json($birReports);

        // Fetch expenses for this branch, grouped by month too
        // $expenses = DB::table('expences_reports')
        //     ->where('branch_id', $branchId)
        //     ->orderBy('created_at', 'desc')
        //     ->get()


        // return response()->json([
        //     'bir_reports' => $birReports,
        //     // 'expenses' => $expenses,
        // ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'branch_id' => 'required|integer',
            'receipt_no' => 'required|integer',
            'tin_no' => 'required|integer',
            'description' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric',
            'category' => 'nullable|string|max:255',
        ]);

        BirReport::create($request->all());

        return response()->json(['message' => 'Bir Report created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(BirReport $birReport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BirReport $birReport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BirReport $birReport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BirReport $birReport)
    {
        //
    }
}
