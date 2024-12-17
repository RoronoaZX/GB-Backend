<?php

namespace App\Http\Controllers;

use App\Models\BakerReports;
use App\Models\BranchProduct;
use App\Models\BranchRawMaterialsReport;
use App\Models\BreadProductionReport;
use App\Models\InitialBakerreports;
use App\Models\InitialFillingBakerreports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class InitialBakerreportsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    $reports = InitialBakerreports::orderBy('created_at', 'desc')->get();

    // Loop through each report to load relationships conditionally
    foreach ($reports as $report) {
        if (strtolower($report->recipe_category) === 'dough') {
            $report->load(['branch','user','branchRecipe','ingredientBakersReports', 'breadBakersReports']);
        } elseif (strtolower($report->recipe_category) === 'filling') {
            $report->load(['branch','user','recipe','ingredientBakersReports', 'fillingBakersReports']);
        }
    }

    // Return the response as JSON
    return response()->json($reports);
}

public function getInitialReportsData()
{
    $reports = InitialBakerreports::with(['branch', 'user', 'branchRecipe', 'breadBakersReports'])
                                  ->orderBy('created_at', 'desc')
                                  ->get();

    return response()->json($reports);
}

    public function getReportsByUserId($userId)
    {
        // Fetch reports by user ID and order by creation date
        $reports = InitialBakerreports::where('user_id', $userId)
                                    ->orderBy('created_at', 'desc')
                                    ->get();

        // Loop through each report to load relationships conditionally
        foreach ($reports as $report) {
            if (strtolower($report->recipe_category) === 'dough') {
                $report->load(['branch','user','branchRecipe','ingredientBakersReports', 'breadBakersReports']);
            } elseif (strtolower($report->recipe_category) === 'filling') {
                $report->load(['branch','user','branchRecipe','ingredientBakersReports', 'fillingBakersReports']);
            }
        }

        // Return the response as JSON
        return response()->json($reports);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reports' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed for reports array',
                'errors' => $validator->errors()
            ], 422);
        }

        foreach ($request->reports as $report) {
            $reportValidator = Validator::make($report, [
                'branch_id' => 'required|integer|exists:branches,id',
                'user_id' => 'required|integer|exists:users,id',
                'branch_recipe_id' => 'required|integer|exists:branch_recipes,id',
                'recipe_category' => 'required|string|in:Dough,Filling',
                'status' => 'required|string|max:255',
                'kilo' => 'required|integer',
                'over' => 'required|integer',
                'short' => 'required|integer',
                'target' => 'required|numeric',
                'actual_target' => 'required|integer',
                'breads' => 'required|array',
                'breads.*.bread_id' => 'required|integer',
                'breads.*.bread_production' => 'required|integer',
                'ingredients' => 'required|array',
                'ingredients.*.ingredients_id' => 'required|integer',
                'ingredients.*.quantity' => 'required|integer',
                'ingredients.*.unit' => 'required|string|max:191',
            ]);

            if ($reportValidator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed for one or more reports',
                    'errors' => $reportValidator->errors()
                ], 422);
            }

            $validatedData = $reportValidator->validated();
            $validatedData['status'] = $report['recipe_category'] === 'Filling' ? 'confirmed' : $report['status'];
            $bakerReport = InitialBakerreports::create($validatedData);

            if ($report['recipe_category'] === 'Dough') {
                if (isset($validatedData['breads'])) {
                    $bakerReport->breadBakersReports()->createMany($validatedData['breads']);
                }
                $bakerReport->ingredientBakersReports()->createMany($validatedData['ingredients']);
            }

            if ($report['recipe_category'] === 'Filling') {
                if (isset($validatedData['breads'])) {
                    $fillingData = array_map(function($bread) {
                        return [
                            'bread_id' => $bread['bread_id'],
                            'filling_production' => $bread['bread_production']
                        ];
                    }, $validatedData['breads']);

                    $bakerReport->fillingBakersReports()->createMany($fillingData);
                }
                $bakerReport->ingredientBakersReports()->createMany($validatedData['ingredients']);

                foreach ($validatedData['ingredients'] as $ingredientReport) {
                    $ingredientInventory = BranchRawMaterialsReport::where('ingredients_id', $ingredientReport['ingredients_id'])
                        ->where('branch_id', $validatedData['branch_id'])
                        ->first();

                    if ($ingredientInventory) {
                        $ingredientInventory->total_quantity -= $ingredientReport['quantity'];
                        $ingredientInventory->save();
                    }
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Reports stored successfully',
        ], 201);
    }


    public function fetchDoughReports($branchId)
    {
        // $user = Auth::user();
        // $branch_id = $user->branch_employee->branch_id;

        $reports = InitialBakerreports::pendingDoughReports()->where('branch_id', $branchId)->with(['branch', 'user', 'branchRecipe', 'breadBakersReports'])->orderBy('created_at', 'desc')->get();

        // Return the response as JSON
        return response()->json($reports);
    }

    public function confirmReport(Request $request, $id)
    {
        $initialReport = InitialBakerreports::with('ingredientBakersReports', 'breadBakersReports.bread')->findOrFail($id);

        if (strtolower($initialReport->status) === 'pending' && strtolower($initialReport->recipe_category) === 'dough') {

            foreach ($initialReport->ingredientBakersReports as $ingredientReport) {
                $ingredientInventory = BranchRawMaterialsReport::where('ingredients_id', $ingredientReport->ingredients_id)
                    ->where('branch_id', $initialReport->branch_id)
                    ->first();

                if ($ingredientInventory) {
                    $ingredientInventory->total_quantity -= $ingredientReport->quantity;
                    $ingredientInventory->save();
                }
            }

            foreach ($initialReport->breadBakersReports as $breadReport) {
                BreadProductionReport::create([
                    'branch_id'=> $initialReport->branch_id,
                    'user_id' => $initialReport->user_id,
                    'branch_recipe_id' => $initialReport->branch_recipe_id,
                    'initial_bakerreports_id' => $initialReport->id,
                    'bread_id' => $breadReport->bread_id,
                    'bread_new_production' => $breadReport->bread_production,
                ]);
                 // Update BranchProduct model
            $branchProduct = BranchProduct::where('branches_id', $initialReport->branch_id)
            ->where('product_id', $breadReport->bread_id)
            ->first();

        if ($branchProduct) {
            $existingTotalQuantity = $branchProduct->total_quantity;

            $branchProduct->new_production = $breadReport->bread_production;
            $branchProduct->total_quantity = $existingTotalQuantity + $branchProduct->new_production;
            $branchProduct->save();
        }
            }


            $initialReport->status = 'confirmed';
            $initialReport->save();

            return response()->json(['message' => 'Report confirmed and inventory updated successfully'], 200);
        }

        return response()->json(['message' => 'Invalid report or status'], 400);
    }


    public function declineReport(Request $request, $id)
    {
        $request->validate([
            'remark' => 'required|string|max:255'
        ]);

        $initialReport = InitialBakerreports::findOrFail($id);

        if ($initialReport->status === 'pending') {
            $initialReport->status = 'declined';
            $initialReport->remark = $request->remark;
            $initialReport->save();

            return response()->json(['message' => 'Report declined successfully']);
        }
        return response()->json(['message' => "Invalid report or status"], 400);
    }
    /**
     * Display the specified resource.
     */
    public function show(InitialBakerreports $initialBakerreports)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InitialBakerreports $initialBakerreports)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InitialBakerreports $initialBakerreports)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InitialBakerreports $initialBakerreports)
    {
        //
    }
}
