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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'branch_id' => 'required|integer',
            'user_id' => 'required|integer',
            'layers' => 'required|integer',
            'name' => 'required|string|max:255',
            'price' => 'required|string|regex:/^\d{1,3}(,\d{3})*(\.\d{2})?$/',
            'ingredients' => 'required|array',
            'ingredients.*.branch_raw_materials_reports_id' => 'required|integer',
            'ingredients.*.quantity' => 'required|numeric',
            'ingredients.*.unit' => 'required|string'
        ]);

        $price = str_replace(',', '', $validatedData['price']);

        $report = CakeReport::create([
            'branch_id' => $validatedData['branch_id'],
            'user_id' => $validatedData['user_id'],
            'layers' => $validatedData['layers'],
            'name' => $validatedData['name'],
            'price' => $price,
        ]);

        foreach ($validatedData['ingredients'] as $ingredient) {
            CakeIngredientReports::create([
                'cake_reports_id' => $report->id,
                'branch_raw_materials_reports_id' => $ingredient['branch_raw_materials_reports_id'],
                'quantity' => $ingredient['quantity'],
                'unit' => $ingredient['unit'],
            ]);
        }

        return response()->json([
            'message' => 'Report created successfully!',
            'report' => $report,
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
