<?php

namespace App\Http\Controllers;

use App\Models\BakerReports;
use App\Models\BranchProduct;
use App\Models\BranchRawMaterialsReport;
use App\Models\BreadProductionReport;
use App\Models\IncentiveEmployeeReports;
use App\Models\IncentivesBases;
use App\Models\IncentivesReports;
use App\Models\InitialBakerreports;
use App\Models\InitialFillingBakerreports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    public function getReportsByUserId(Request $request, $userId)
    {
        // Get pagination size (default to 10 if not provided)
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1); // Default to page 1

        // Fetch reports by user ID and order by creation date
        $reports = InitialBakerreports::where('user_id', $userId)
                                        ->orderBy('created_at', 'desc')
                                        ->paginate($perPage, ['*'], 'page', $page);

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

    public function adminCreateReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reports'    => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'     => 'error',
                'message'    => 'Validation failed for reports array',
                'errors'     => $validator->errors()
            ], 422);
        }

        foreach ($request->reports as $report) {
            $reportValidator = Validator::make($report, [
                'branch_id'                          => 'required|integer|exists:branches,id',
                'user_id'                            => 'required|integer|exists:users,id',
                'branch_recipe_id'                   => 'required|integer|exists:branch_recipes,id',
                'recipe_category'                    => 'required|string|in:Dough,Filling',
                'status'                             => 'required|string|max:255',
                'kilo'                               => 'required|numeric',
                'over'                               => 'required|integer',
                'short'                              => 'required|integer',
                'target'                             => 'required|numeric',
                'actual_target'                      => 'required|integer',
                'breads'                             => 'required|array',
                'breads.*.bread_id'                  => 'required|integer',
                'breads.*.bread_production'          => 'required|integer',
                'ingredients'                        => 'required|array',
                'ingredients.*.ingredients_id'       => 'required|integer',
                'ingredients.*.quantity'             => 'required|numeric',
                'ingredients.*.unit'                 => 'required|string|max:191',
                'created_at'                         => 'nullable|date', // Optional created_at
            ]);

            if ($reportValidator->fails()) {
                return response()->json([
                    'status'     => 'error',
                    'message'    => 'Validation failed for one or more reports',
                    'errors'     => $reportValidator->errors()
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
            }

            // ✅ Shared logic to deduct ingredients from inventory (applies to both Dough and Filling)
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

        return response()->json([
            'status'     => 'success',
            'message'    => 'Reports stored successfully',
        ], 201);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reports'            => 'required|array',
            'employee_in_shift'  => 'required|array',
            'overall_kilo'       => 'required|numeric',
            'total_employees'    => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'     => 'error',
                'message'    => 'Validation failed for reports array',
                'errors'     => $validator->errors()
            ], 422);
        }

        foreach ($request->reports as $report) {
            $reportValidator = Validator::make($report, [
                'branch_id'                      => 'required|integer|exists:branches,id',
                'user_id'                        => 'required|integer|exists:users,id',
                'branch_recipe_id'               => 'required|integer|exists:branch_recipes,id',
                'recipe_category'                => 'required|string|in:Dough,Filling',
                'status'                         => 'required|string|max:255',
                'kilo'                           => 'required|numeric',
                'over'                           => 'required|integer',
                'short'                          => 'required|integer',
                'target'                         => 'required|numeric',
                'actual_target'                  => 'required|integer',
                'breads'                         => 'required|array',
                'breads.*.bread_id'              => 'required|integer',
                'breads.*.bread_production'      => 'required|integer',
                'ingredients'                    => 'required|array',
                'ingredients.*.ingredients_id'   => 'required|integer',
                'ingredients.*.quantity'         => 'required|numeric',
                'ingredients.*.unit'             => 'required|string|max:191',
            ]);

            if ($reportValidator->fails()) {
                return response()->json([
                    'status'     => 'error',
                    'message'    => 'Validation failed for one or more reports',
                    'errors'     => $reportValidator->errors()
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
                            'bread_id'               => $bread['bread_id'],
                            'filling_production'     => $bread['bread_production']
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

        // Get branch_id form the first report (assuming al reports have the same branch_id)
        $branch_id = $request->reports[0]['branch_id'];

        foreach ($request->employee_in_shift as $shift) {
            // Create incentive_employee_reports records(s)
            IncentiveEmployeeReports::create([
                'branch_id'              => $branch_id,
                'employee_id'            => $shift['employee_id'],
                'number_of_employees'    => $request->total_employees,
                'designation'            => $shift['designation'],
                'shift_status'           => $shift['shift_status']
            ]);
        }

        return response()->json([
            'status'     => 'success',
            'message'    => 'Reports stored successfully',
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
                    'branch_id'                  => $initialReport->branch_id,
                    'user_id'                    => $initialReport->user_id,
                    'branch_recipe_id'           => $initialReport->branch_recipe_id,
                    'initial_bakerreports_id'    => $initialReport->id,
                    'bread_id'                   => $breadReport->bread_id,
                    'bread_new_production'       => $breadReport->bread_production,
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

    //             // Update related bread reports
//             if ($recipeCategory === 'Dough') {
//                 $bakerReport->breadBakersReports()->delete();
//                 $bakerReport->breadBakersReports()->createMany($validatedData['combined_bakers_reports']);
// // Retrieve existing bread production reports
// $existingBreadReports = $bakerReport->breadProductionReports()->get()->keyBy('bread_id');

// $breadReportsData = array_map(function ($bread) use ($existingBreadReports) {
//     // Check if the record exists and if the production value has changed
//     if (
//         !isset($existingBreadReports[$bread['bread_id']]) ||
//         $existingBreadReports[$bread['bread_id']]->bread_new_production != $bread['bread_production']
//     ) {
//         return [
//             'bread_id' => $bread['bread_id'],
//             'bread_new_production' => $bread['bread_production'],
//         ];
//     }
//     return null; // Skip if no update is needed
// }, $validatedData['combined_bakers_reports']);

// // Filter out null values to only include updates
// $breadReportsData = array_filter($breadReportsData);

// if (!empty($breadReportsData)) {
//     // Delete and recreate only updated records
//     $bakerReport->breadProductionReports()->delete();
//     $bakerReport->breadProductionReports()->createMany($breadReportsData);
// }
//             }

    public function updateBakersReport($id, Request $request)
    {
        $bakerReport = InitialBakerreports::find($id);

        if (!$bakerReport) {
            return response()->json([
                'status' => 'error',
                'message' => 'Baker report not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'initial_bakerreports_id'                        => 'required|integer',
            'sales_report_id'                                => 'required|integer',
            'category'                                       => 'required|string|in:Dough,Filling',
            'status'                                         => 'sometimes|string|max:255',
            'kilo'                                           => 'required|numeric',
            'over'                                           => 'required|integer',
            'short'                                          => 'required|integer',
            'target'                                         => 'required|numeric',
            'actual_target'                                  => 'required|integer',
            'combined_bakers_reports'                        => 'required|array',
            'combined_bakers_reports.*.bread_id'             => 'required|integer',
            'combined_bakers_reports.*.bread_production'     => 'required|numeric',
            'recalculated_ingredients'                       => 'required|array',
            'recalculated_ingredients.*.ingredients_id'      => 'required|integer',
            'recalculated_ingredients.*.quantity'            => 'required|numeric',
            'recalculated_ingredients.*.unit'                => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'     => 'error',
                'message'    => 'Validation failed',
                'errors'     => $validator->errors()
            ], 422);
        }

        $validatedData = $validator->validated();
        $recipeCategory = $validatedData['category'];

        DB::beginTransaction();

        try {
            // Update the main baker report
            $bakerReport->update([
                'kilo'           => $validatedData['kilo'],
                'over'           => $validatedData['over'],
                'short'          => $validatedData['short'],
                'target'         => $validatedData['target'],
                'actual_target'  => $validatedData['actual_target'],
                'status'         => $recipeCategory === 'Filling' ? 'confirmed' : ($validatedData['status'] ?? $bakerReport->status),
            ]);

            // Update related bread reports
            if ($recipeCategory === 'Dough') {
                $bakerReport->breadBakersReports()->delete();
                $bakerReport->breadBakersReports()->createMany($validatedData['combined_bakers_reports']);

                // Update only the bread_new_production for existing bread reports
                foreach ($validatedData['combined_bakers_reports'] as $bread) {
                    $bakerReport->breadProductionReports()
                        ->where('bread_id', $bread['bread_id'])
                        ->update([
                            'bread_new_production' => $bread['bread_production']
                        ]);

                // Update new_production in bread_sales_report
                DB::table('bread_sales_reports')
                        ->where('product_id', $bread['bread_id'])
                        ->where('sales_report_id', $validatedData['sales_report_id'])
                        ->update(['new_production' => $bread['bread_production']]);
                }
            }
           // Update related bread reports


            if ($recipeCategory === 'Filling') {
                $bakerReport->fillingBakersReports()->delete();
                $fillingData = array_map(function ($bread) {
                    return [
                        'bread_id' => $bread['bread_id'],
                        'filling_production' => $bread['bread_production']
                    ];
                }, $validatedData['combined_bakers_reports']);
                $bakerReport->fillingBakersReports()->createMany($fillingData);
            }

            // Update related ingredient reports
            $bakerReport->ingredientBakersReports()->delete();
            $bakerReport->ingredientBakersReports()->createMany($validatedData['recalculated_ingredients']);

            // if ($recipeCategory === 'Filling') {
            //     foreach ($validatedData['recalculated_ingredients'] as $ingredientReport) {
            //         $ingredientInventory = BranchRawMaterialsReport::where('ingredients_id', $ingredientReport['ingredients_id'])
            //             ->where('branch_id', $bakerReport->branch_id)
            //             ->first();

            //         if ($ingredientInventory) {
            //             $ingredientInventory->total_quantity -= $ingredientReport['quantity'];
            //             $ingredientInventory->save();
            //         }
            //     }
            // }

            DB::commit();

            return response()->json([
                'status'     => 'success',
                'message'    => 'Baker report updated successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'     => 'error',
                'message'    => 'Failed to update baker report',
                'error'      => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InitialBakerreports $initialBakerreports)
    {
        //
    }
}
