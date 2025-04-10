<?php

namespace App\Http\Controllers;

use App\Models\BirReport;
use App\Models\Branch;
use App\Models\ExpencesReport;
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
    public function updateBranchDescriptionForReports(Request $request, $id)
    {
        $validatedData = $request->validate([
            'description' => 'required|string|max:255',
        ]);

        $updated = BirReport::where('id', $id)->update([
            'description' => $validatedData['description']
        ]);

        if ($updated) {
            return response()->json(['message' => 'Branch description updated successfully'], 200);
        } else {
            return response()->json(['message' => 'No records were updated or branch not found'], 404);
        }
    }

    public function updateReceiptNoForReports(Request $request, $id)
    {
        $validatedData = $request->validate([
            'receipt_no' => 'required|integer',
        ]);

        $updated = BirReport::where('id', $id)->update([
            'receipt_no' => $validatedData['receipt_no']
        ]);

        if ($updated) {
            return response()->json(['message' => 'Receipt number updated successfully'], 200);
        } else {
            return response()->json(['message' => 'No records were updated or branch not found'], 404);
        }
    }

    public function updateAddressReports(Request $request, $id)
    {
        $validatedData = $request->validate([
            'address' => 'required|string|max:255',
        ]);

        $updated = BirReport::where('id', $id)->update([
            'address' => $validatedData['address']
        ]);

        if ($updated) {
            return response()->json(['message' => 'Address updated successfully'], 200);
        } else {
            return response()->json(['message' => 'No records were updated or branch not found'], 404);
        }
    }

    public function updateTinNoForReports(Request $request, $id)
    {
        $validatedData = $request->validate([
            'tin_no' => 'required|integer',
        ]);

        $updated = BirReport::where('id', $id)->update([
            'tin_no' => $validatedData['tin_no']
        ]);

        if ($updated) {
            return response()->json(['message' => 'TIN number updated successfully'], 200);
        } else {
            return response()->json(['message' => 'No records were updated or branch not found'], 404);
        }
    }

    public function updateAmountForReports(Request $request, $id)
    {
        $validatedData = $request->validate([
            'amount' => 'required|numeric',
        ]);

        $updated = BirReport::where('id', $id)->update([
            'amount' => $validatedData['amount']
        ]);

        if ($updated) {
            return response()->json(['message' => 'Amount updated successfully'], 200);
        } else {
            return response()->json(['message' => 'No records were updated or branch not found'], 404);
        }
    }

    public function updateDateForReports(Request $request, $id)
    {
        $validatedData = $request->validate([
            'created_at' => 'required|date',
        ]);

        $updated = BirReport::where('id', $id)->update([
            'created_at' => Carbon::parse($validatedData['created_at'])
        ]);

        if ($updated) {
            return response()->json(['message' => 'Date updated successfully'], 200);
        } else {
            return response()->json(['message' => 'No records were updated or branch not found'], 404);
        }
    }

    public function fetchBranchDataForReports($branchId)
    {
        $branchDataBirReports = Branch::where('id', $branchId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($branchDataBirReports);
    }

    // public function fetchNonVATBirReports(Request $request,$branchId)
    // {

    //     $startDate = $request->query('startDate');
    //     $endDate = $request->query('endDate');

    //     $birReports = BirReport::where('branch_id', $branchId)
    //         ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
    //             $query->whereBetween('created_at', [$startDate, $endDate]);
    //         })
    //         ->with(['user', 'branch'])
    //         ->orderBy('created_at', 'desc')
    //         ->get()
    //         ->map(function ($report) {
    //             $report->category = 'Non-VAT';
    //             return $report;
    //         });

    //     return response()->json($birReports);

    // }

    public function fetchNonVATBirReports(Request $request, $branchId)
    {
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        $birReports = BirReport::where('branch_id', $branchId)
            ->where('category', 'Non-VAT') // filter for Non-VAT only
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->with(['user', 'branch'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($birReports);
    }

    public function fetchVATBirReports(Request $request,$branchId)
    {

        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        $birReports = BirReport::where('branch_id', $branchId)
            ->where('category', 'VAT') // filter for Non-VAT only
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->with(['user', 'branch'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($birReports);

    }
    public function fetchExpensesReports(Request $request,$branchId)
    {

        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        $birReports = ExpencesReport::where('branch_id', $branchId)
            ->where('category', '!=', 'premium') // filter for Non-VAT only
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->with(['user', 'branch'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($birReports);

    }

    /**
     * Store a newly created resource in storage.
     */

    public function savingBIRReportAdmin(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|integer',
            'branch_id' => 'required|integer',
            'receipt_no' => 'required|integer',
            'tin_no' => 'required|integer',
            'description' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'amount' => 'nullable|numeric',
            'category' => 'nullable|string|max:255',
            'created_at' => 'nullable|date',
        ]);

        BirReport::create($validatedData);

        return response()->json(['message' => 'Bir Report created successfully'], 201);
    }

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
