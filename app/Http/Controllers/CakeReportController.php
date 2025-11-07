<?php

namespace App\Http\Controllers;

use App\Models\CakeIngredientReports;
use App\Models\CakeReport;
use App\Models\IngredientGroup;
use Illuminate\Http\Request;

class CakeReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }


    public function getBranchCakeReport($userId)
    {
        $cakeReports = CakeReport::where('user_id', $userId)
                        ->orderBy('created_at', 'desc')
                        ->with('user','branch','cakeIngredientReports' )
                        ->get();
        return response()->json($cakeReports);
    }

    public function getPendingReport($branchId)
    {
        $cakeReports = CakeReport::where('branch_id', $branchId)
                        ->where('confirmation_status', 'pending')
                        ->orderBy('created_at', 'desc')
                        ->with('user', 'branch')
                        ->get();

        return response()->json($cakeReports);
    }

    public function getCakeOnDisplayProduct($branchId)
    {
        $cakeProducts = CakeReport::where('branch_id', $branchId)
                        ->where('sales_status', 'on display')
                        ->orderBy('created_at', 'desc')
                        ->get();
        return response()->json($cakeProducts);
    }

    public function confirmReport($id)
    {
        $cakeReport = CakeReport::findOrFail($id);
        if (strtolower($cakeReport->confirmation_status) === 'pending')
        {
           $cakeReport->confirmation_status  = "confirmed";
           $cakeReport->sales_status         = "on display";
           $cakeReport->save();
           return response()->json([
            'message'    => 'Report has been confirmed successfully.',
            'data'       => $cakeReport
        ], 200);
        }

        // If the report is not pending, return an error message
        return response()->json([
            'message'    => 'Report is already confirmed or cannot be confirmed.',
            'data'       => $cakeReport
        ], 400);
    }

    public function declineReport(Request $request, $id)
    {
        $request->validate([
            'remark' => 'required|string|max:255'
        ]);

        $cakeReport = CakeReport::findOrFail($id);

        if ($cakeReport->confirmation_status === 'pending') {
                $cakeReport->confirmation_status     = 'declined';
                $cakeReport->sales_status            = 'declined';
                $cakeReport->remark                  = $request->remark;
                $cakeReport->save();

                return response()->json([
                    'message'   => "Report declined successfully"
                ], 200);
        }
        return response()->json([
            'message' => "Invalid report or status"
        ], 400);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'branch_id'                                      => 'required|integer',
            'user_id'                                        => 'required|integer',
            'layers'                                         => 'required|integer',
            // 'pieces' => 'required|integer',
            'name'                                           => 'required|string|max:255',
            'confirmation_status'                            => 'required|string|max:255',
            'price'                                          => 'required|string|regex:/^\d{1,3}(,\d{3})*(\.\d{2})?$/',
            'ingredients'                                    => 'required|array',
            'ingredients.*.branch_raw_materials_reports_id'  => 'required|integer',
            'ingredients.*.quantity'                         => 'required|numeric',
            'ingredients.*.unit'                             => 'required|string'
        ]);

        $price = str_replace(',', '', $validatedData['price']);

        $report = CakeReport::create([
            'branch_id'              => $validatedData['branch_id'],
            'user_id'                => $validatedData['user_id'],
            'layers'                 => $validatedData['layers'],
            // 'pieces' => $validatedData['pieces'],
            'confirmation_status'    => $validatedData['confirmation_status'],
            'name'                   => $validatedData['name'],
            'price'                  => $price,
        ]);

        foreach ($validatedData['ingredients'] as $ingredient) {
            CakeIngredientReports::create([
                'cake_reports_id'                      => $report->id,
                'branch_raw_materials_reports_id'      => $ingredient['branch_raw_materials_reports_id'],
                'quantity'                             => $ingredient['quantity'],
                'unit'                                 => $ingredient['unit'],
            ]);
        }

        return response()->json([
            'message'    => 'Report created successfully!',
            'report'     => $report,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(CakeReport $cakeReport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CakeReport $cakeReport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CakeReport $cakeReport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CakeReport $cakeReport)
    {
        //
    }
}
