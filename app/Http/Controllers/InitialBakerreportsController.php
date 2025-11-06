<?php

namespace App\Http\Controllers;

use App\Models\BakerReports;
use App\Models\BranchProduct;
use App\Models\BranchRawMaterialsReport;
use App\Models\BranchRecipe;
use App\Models\BranchRmStocks;
use App\Models\BreadProductionReport;
use App\Models\IncentiveEmployeeReports;
use App\Models\IncentivesBases;
use App\Models\IncentivesReports;
use App\Models\InitialBakerreports;
use App\Models\InitialFillingBakerreports;
use App\Models\RecipeCost;
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

    // public function getReportsByUserId(Request $request, $userId)
    // {
    //     // Get pagination size (default to 10 if not provided)
    //     $perPage = $request->query('per_page', 10);
    //     $page = $request->query('page', 1); // Default to page 1

    //     // Fetch reports by user ID and order by creation date
    //     $reports = InitialBakerreports::where('user_id', $userId)
    //                                     ->orderBy('created_at', 'desc')
    //                                     ->paginate($perPage, ['*'], 'page', $page);

    //     // Loop through each report to load relationships conditionally
    //     foreach ($reports as $report) {
    //         if (strtolower($report->recipe_category) === 'dough') {
    //             $report->load(['branch','user','branchRecipe','ingredientBakersReports', 'breadBakersReports']);
    //         } elseif (strtolower($report->recipe_category) === 'filling') {
    //             $report->load(['branch','user','branchRecipe','ingredientBakersReports', 'fillingBakersReports']);
    //         }
    //     }

    //     // Return the response as JSON
    //     return response()->json($reports);
    // }

    // public function getReportsByUserId(Request $request, $userId)
    // {
    //     try {
    //         $perPage = (int) $request->query('per_page', 10);
    //         $page = (int) $request->query('page', 1);
    //         // $search = $request->query('search'); // added search

    //         // // ✅ Base query
    //         // $reportsQuery =

    //         $reports = InitialBakerreports::where('user_id', $userId)
    //             ->orderBy('created_at', 'desc')
    //             ->paginate($perPage, ['*'], 'page', $page);

    //         // ✅ Load relational data
    //         foreach ($reports as $report) {
    //             if (strtolower($report->recipe_category) === 'dough') {
    //                 $report->load(['branch','user','branchRecipe','ingredientBakersReports', 'breadBakersReports']);
    //             } elseif (strtolower($report->recipe_category) === 'filling') {
    //                 $report->load(['branch','user','branchRecipe','ingredientBakersReports', 'fillingBakersReports']);
    //             }
    //         }

    //         // ✅ Format output the same as fetchDeliveryStocksBranch()
    //         return response()->json([
    //             'success' => true,
    //             'data' => $reports->map(function ($report) {
    //                 return [
    //                     'id'                         => $report->id,
    //                     'actual_target'              => $report->actual_target,
    //                     'branch'                     => $report->branch,
    //                     'branch_id'                  => $report->branch_id,
    //                     'branch_recipe'              => $report->branchRecipe,
    //                     'branch_recipe_id'           => $report->branch_recipe_id,
    //                     'ingredient_bakers_reports'  => $report->ingredientBakersReports,
    //                     'kilo'                       => $report->kilo,
    //                     'over'                       => $report->over,
    //                     'recipe_category'            => $report->recipe_category,
    //                     'remark'                     => $report->remark,
    //                     'short'                      => $report->short,
    //                     'status'                     => $report->status,
    //                     'target'                     => $report->target,
    //                     'user'                       => $report->user,
    //                     'user_id'                    => $report->user_id,
    //                     'created_at'                 => $report->created_at,
    //                     'updated_at'                 => $report->updated_at,

    //                     // ✅ CONDITIONAL: Only return one of the two based on category
    //                      'bread_bakers_reports' =>
    //                         strtolower($report->recipe_category) === 'dough'
    //                             ? $report->breadBakersReports
    //                             : null,

    //                     'filling_bakers_reports' =>
    //                         strtolower($report->recipe_category) === 'filling'
    //                             ? $report->fillingBakersReports
    //                             : null,
    //                 ];
    //             }),

    //             'pagination' => [
    //                 'total'         => $reports->total(),
    //                 'per_page'      => $reports->perPage(),
    //                 'current_page'  => $reports->currentPage(),
    //                 'last_page'     => $reports->lastPage(),
    //                 'from'          => $reports->firstItem(),
    //                 'to'            => $reports->lastItem(),
    //             ]
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Failed to fetch reports',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function getReportsByUserId(Request $request, $userId)
{
    try {
        $perPage = (int) $request->query('per_page', 10);
        $page = (int) $request->query('page', 1);
        $search = $request->query('search'); // ✅ added search

        // ✅ Base query
        $reportsQuery = InitialBakerreports::where('user_id', $userId);

        // ✅ Search by recipe name in related models
        if ($search) {
            $reportsQuery->whereHas('branchRecipe.recipe', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // ✅ Order and paginate
        $reports = $reportsQuery
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // ✅ Load relations depending on category
        foreach ($reports as $report) {
            if (strtolower($report->recipe_category) === 'dough') {
                $report->load(['branch','user','branchRecipe','ingredientBakersReports', 'breadBakersReports']);
            } elseif (strtolower($report->recipe_category) === 'filling') {
                $report->load(['branch','user','branchRecipe','ingredientBakersReports', 'fillingBakersReports']);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $reports->map(function ($report) {
                return [
                    'id'                        => $report->id,
                    'actual_target'             => $report->actual_target,
                    'branch'                    => $report->branch,
                    'branch_id'                 => $report->branch_id,
                    'branch_recipe'             => $report->branchRecipe,
                    'branch_recipe_id'          => $report->branch_recipe_id,
                    'ingredient_bakers_reports' => $report->ingredientBakersReports,
                    'kilo'                      => $report->kilo,
                    'over'                      => $report->over,
                    'recipe_category'           => $report->recipe_category,
                    'remark'                    => $report->remark,
                    'short'                     => $report->short,
                    'status'                    => $report->status,
                    'target'                    => $report->target,
                    'user'                      => $report->user,
                    'user_id'                   => $report->user_id,
                    'created_at'                => $report->created_at,
                    'updated_at'                => $report->updated_at,

                    // ✅ Conditional return
                    'bread_bakers_reports' =>
                        strtolower($report->recipe_category) === 'dough'
                            ? $report->breadBakersReports
                            : null,

                    'filling_bakers_reports' =>
                        strtolower($report->recipe_category) === 'filling'
                            ? $report->fillingBakersReports
                            : null,
                ];
            }),

            'pagination' => [
                'total'        => $reports->total(),
                'per_page'     => $reports->perPage(),
                'current_page' => $reports->currentPage(),
                'last_page'    => $reports->lastPage(),
                'from'         => $reports->firstItem(),
                'to'           => $reports->lastItem(),
            ]

        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Failed to fetch reports',
            'error' => $e->getMessage()
        ], 500);
    }
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

    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'reports'            => 'required|array',
    //         'employee_in_shift'  => 'required|array',
    //         'overall_kilo'       => 'required|numeric',
    //         'total_employees'    => 'required|numeric',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status'     => 'error',
    //             'message'    => 'Validation failed for reports array',
    //             'errors'     => $validator->errors()
    //         ], 422);
    //     }

    //     foreach ($request->reports as $report) {
    //         $reportValidator = Validator::make($report, [
    //             'branch_id'                      => 'required|integer|exists:branches,id',
    //             'user_id'                        => 'required|integer|exists:users,id',
    //             'branch_recipe_id'               => 'required|integer|exists:branch_recipes,id',
    //             'recipe_category'                => 'required|string|in:Dough,Filling',
    //             'status'                         => 'required|string|max:255',
    //             'kilo'                           => 'required|numeric',
    //             'over'                           => 'required|integer',
    //             'short'                          => 'required|integer',
    //             'target'                         => 'required|numeric',
    //             'actual_target'                  => 'required|integer',
    //             'breads'                         => 'required|array',
    //             'breads.*.bread_id'              => 'required|integer',
    //             'breads.*.bread_production'      => 'required|integer',
    //             'ingredients'                    => 'required|array',
    //             'ingredients.*.ingredients_id'   => 'required|integer',
    //             'ingredients.*.quantity'         => 'required|numeric',
    //             'ingredients.*.unit'             => 'required|string|max:191',
    //         ]);

    //         if ($reportValidator->fails()) {
    //             return response()->json([
    //                 'status'     => 'error',
    //                 'message'    => 'Validation failed for one or more reports',
    //                 'errors'     => $reportValidator->errors()
    //             ], 422);
    //         }

    //         $validatedData = $reportValidator->validated();
    //         $validatedData['status'] = $report['recipe_category'] === 'Filling' ? 'confirmed' : $report['status'];
    //         $bakerReport = InitialBakerreports::create($validatedData);

    //         if ($report['recipe_category'] === 'Dough') {
    //             if (isset($validatedData['breads'])) {
    //                 $bakerReport->breadBakersReports()->createMany($validatedData['breads']);
    //             }
    //             $bakerReport->ingredientBakersReports()->createMany($validatedData['ingredients']);
    //         }

    //         if ($report['recipe_category'] === 'Filling') {
    //             if (isset($validatedData['breads'])) {
    //                 $fillingData = array_map(function($bread) {
    //                     return [
    //                         'bread_id'               => $bread['bread_id'],
    //                         'filling_production'     => $bread['bread_production']
    //                     ];
    //                 }, $validatedData['breads']);

    //                 $bakerReport->fillingBakersReports()->createMany($fillingData);
    //             }
    //             $bakerReport->ingredientBakersReports()->createMany($validatedData['ingredients']);

    //             foreach ($validatedData['ingredients'] as $ingredientReport) {
    //                 $ingredientInventory = BranchRawMaterialsReport::where('ingredients_id', $ingredientReport['ingredients_id'])
    //                     ->where('branch_id', $validatedData['branch_id'])
    //                     ->first();

    //                 if ($ingredientInventory) {
    //                     $ingredientInventory->total_quantity -= $ingredientReport['quantity'];
    //                     $ingredientInventory->save();
    //                 }
    //             }
    //         }
    //     }

    //     // Get branch_id form the first report (assuming al reports have the same branch_id)
    //     $branch_id = $request->reports[0]['branch_id'];

    //     foreach ($request->employee_in_shift as $shift) {
    //         // Create incentive_employee_reports records(s)
    //         IncentiveEmployeeReports::create([
    //             'branch_id'              => $branch_id,
    //             'employee_id'            => $shift['employee_id'],
    //             'number_of_employees'    => $request->total_employees,
    //             'designation'            => $shift['designation'],
    //             'shift_status'           => $shift['shift_status']
    //         ]);
    //     }

    //     return response()->json([
    //         'status'     => 'success',
    //         'message'    => 'Reports stored successfully',
    //     ], 201);
    // }

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

            $initialReport = InitialBakerreports::create($validatedData);
            $branchRecipe = BranchRecipe::find($initialReport->branch_recipe_id);
            $recipe_id = $branchRecipe->recipe_id;

            // 🍞 Dough logic
            if ($report['recipe_category'] === 'Dough') {
                if (isset($validatedData['breads'])) {
                    $initialReport->breadBakersReports()->createMany($validatedData['breads']);
                }
                $initialReport->ingredientBakersReports()->createMany($validatedData['ingredients']);
            }

            // 🍫 Filling logic
            if ($report['recipe_category'] === 'Filling') {
                if (isset($validatedData['breads'])) {
                    $fillingData = array_map(function($bread) {
                        return [
                            'bread_id'           => $bread['bread_id'],
                            'filling_production' => $bread['bread_production']
                        ];
                    }, $validatedData['breads']);

                    $initialReport->fillingBakersReports()->createMany($fillingData);
                }

                $initialReport->ingredientBakersReports()->createMany($validatedData['ingredients']);

                foreach ($validatedData['ingredients'] as $ingredientReport) {
                    // 🧩 Added FIFO Deduction Logic for Filling Category
                    $remainingQtyToDeduct = $ingredientReport['quantity'];
                    $ingredientTotalCost = 0;
                    $grandTotal = 0;
                    $stockFound = false;
                    $recipeId = $recipe_id;

                    $branchStocks = BranchRmStocks::where('raw_material_id', $ingredientReport['ingredients_id'])
                        ->where('branch_id', $initialReport->branch_id)
                        ->where('quantity', '>', 0)
                        ->orderBy('created_at', 'asc')
                        ->lockForUpdate()
                        ->get();

                    foreach ($branchStocks as $stock) {
                        if ($remainingQtyToDeduct <= 0) break;

                        $stockFound = true;
                        $deductQty = min($remainingQtyToDeduct, $stock->quantity);

                        $unitPrice = $stock->price_per_gram ?? 0;
                        $cost = $deductQty * $unitPrice;

                        $stock->quantity = max(0, $stock->quantity - $deductQty);
                        $stock->save();

                        RecipeCost::create([
                            'branch_rm_stock_id'         => $stock->id,
                            'user_id'                    => $initialReport->user_id,
                            'branch_id'                  => $initialReport->branch_id,
                            'recipe_id'                  => $recipeId,
                            'recipe_category'            => $initialReport->recipe_category,
                            'raw_material_id'            => $ingredientReport['ingredients_id'],
                            'initial_bakerreport_id'     => $initialReport->id,
                            'branch_recipe_id'           => $initialReport->branch_recipe_id,
                            'quantity_used'              => $deductQty,
                            'price_per_gram'             => $unitPrice,
                            'total_cost'                 => $cost,
                            'status'                     => 'confirmed',
                            'kilo'                       => $initialReport->kilo,
                        ]);

                        $ingredientTotalCost += $cost;
                        $grandTotal += $cost;
                        $remainingQtyToDeduct -= $deductQty;
                    }

                    // ⚠️ No stock found — record as missing
                    if (!$stockFound) {
                        RecipeCost::create([
                            'branch_rm_stock_id'         => null,
                            'user_id'                    => $initialReport->user_id,
                            'branch_id'                  => $initialReport->branch_id,
                            'recipe_id'                  => $recipeId,
                            'recipe_category'            => $initialReport->recipe_category,
                            'raw_material_id'            => $ingredientReport['ingredients_id'],
                            'initial_bakerreport_id'     => $initialReport->id,
                            'branch_recipe_id'           => $initialReport->branch_recipe_id,
                            'quantity_used'              => $ingredientReport['quantity'],
                            'price_per_gram'             => 0,
                            'total_cost'                 => 0,
                            'status'                     => 'missing_stock',
                            'kilo'                       => $initialReport->kilo,
                        ]);
                    }

                    // 🧾 Update ingredient inventory total
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

        // Get branch_id form the first report (assuming all reports have the same branch_id)
        $branch_id = $request->reports[0]['branch_id'];

        foreach ($request->employee_in_shift as $shift) {
            IncentiveEmployeeReports::create([
                'branch_id'              => $branch_id,
                'employee_id'            => $shift['employee_id'],
                'number_of_employees'    => $request->total_employees,
                'designation'            => $shift['designation'],
                'shift_status'           => $shift['shift_status']
            ]);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Reports stored successfully',
        ], 201);
    }

    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'reports'            => 'required|array',
    //         'employee_in_shift'  => 'required|array',
    //         'overall_kilo'       => 'required|numeric',
    //         'total_employees'    => 'required|numeric',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'Validation failed for reports array',
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     foreach ($request->reports as $report) {
    //         $reportValidator = Validator::make($report, [
    //             'branch_id'                      => 'required|integer|exists:branches,id',
    //             'user_id'                        => 'required|integer|exists:users,id',
    //             'branch_recipe_id'               => 'required|integer|exists:branch_recipes,id',
    //             'recipe_category'                => 'required|string|in:Dough,Filling',
    //             'status'                         => 'required|string|max:255',
    //             'kilo'                           => 'required|numeric',
    //             'over'                           => 'required|integer',
    //             'short'                          => 'required|integer',
    //             'target'                         => 'required|numeric',
    //             'actual_target'                  => 'required|integer',
    //             'breads'                         => 'required|array',
    //             'breads.*.bread_id'              => 'required|integer',
    //             'breads.*.bread_production'      => 'required|integer',
    //             'ingredients'                    => 'required|array',
    //             'ingredients.*.ingredients_id'   => 'required|integer',
    //             'ingredients.*.quantity'         => 'required|numeric',
    //             'ingredients.*.unit'             => 'required|string|max:191',
    //         ]);

    //         if ($reportValidator->fails()) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Validation failed for one or more reports',
    //                 'errors' => $reportValidator->errors()
    //             ], 422);
    //         }

    //         $validatedData = $reportValidator->validated();
    //         $validatedData['status'] = $report['recipe_category'] === 'Filling' ? 'confirmed' : $report['status'];

    //         $initialReport = InitialBakerreports::create($validatedData);

    //         // 🍞 Dough logic
    //         if ($report['recipe_category'] === 'Dough') {
    //             if (isset($validatedData['breads'])) {
    //                 $initialReport->breadBakerReports()->createMany($validatedData['breads']);
    //             }
    //             $initialReport->ingredientBakersReports()->creaateMany($validatedData['ingredients']);
    //         }

    //         // 🍫 Filling logic
    //         if ($report['recipe_category'] === 'Filling') {
    //             if (isset($validatedData['breads'])) {
    //                 $fillingData = array_map(function($bread) {
    //                     return [
    //                         'bread_id' => $bread['bread_id'],
    //                         'filling_production' => $bread['bread_production']
    //                     ];
    //                 }, $validatedData['breads']);

    //                 $initialReport->fillingBakersReports()->createMany($fillingData);
    //             }

    //             $initialReport->ingredientBakersReports()->createMany($validatedData['ingredients']);

    //             foreach ($validatedData['ingredients'] as $ingredientReport) {
    //                 // 🧩 Added FIFO Deduction Logic for Filling Category
    //                 $remainingQtyToDeduct = $ingredientReport['quantity'];
    //                 $ingredientTotalCost = 0;
    //                 $grandTotal = 0;
    //                 $stockFound = false;
    //                 $recipeId = $initialReport->branch_recipe_id;

    //                 $branchStocks = BranchRmStocks::where('raw_material_id', $ingredientReport['ingredients_id'])
    //                         ->where('branch_id', $initialReport->branch_id)
    //                         ->where('quantity', '>', 0)
    //                         ->orderBy('created_at', 'asc')
    //                         ->lockForUpdate()
    //                         ->get();

    //                 foreach ($branchStocks as $stock) {
    //                     if ($remainingQtyToDeduct <= 0) break;

    //                     $stockFound = true;
    //                     $deductQty = min($remainingQtyToDeduct, $stock->quantity);

    //                     $unitPrice = $stock->price_per_gram ?? 0;
    //                     $cost = $deductQty * $unitPrice;

    //                     $stock->quantity = max(0, $stock->quantity - $deductQty);
    //                     $stock->save();

    //                     RecipeCost::create([
    //                         'branch_rm_stock_id'         => $stock->id,
    //                         'user_id'                    => $initialReport->user_id,
    //                         'branch_id'                  => $initialReport->branch_id,
    //                         'recipe_id'                  => $recipeId,
    //                         'raw_material_id'            => $ingredientReport['ingredients_id'],
    //                         'initial_bakerreport_id'     => $initialReport->id,
    //                         'branch_recipe_id'           => $initialReport->branch_recipe_id,
    //                         'quantity_used'              => $deductQty,
    //                         'price_per_gram'             => $unitPrice,
    //                         'total_cost'                 => $cost,
    //                         'status'                     => 'confirmed',
    //                         'kilo'                       => $initialReport->kilo
    //                     ]);

    //                     $ingredientTotalCost += $cost;
    //                     $grandTotal += $cost;
    //                     $remainingQtyToDeduct -= $deductQty;
    //                 }

    //                 // ⚠️ No stock found — record as missing
    //                 if (!$stockFound) {
    //                     RecipeCost::create([
    //                         'branch_rm_stock_id'         => null,
    //                         'user_id'                    => $initialReport->user_id,
    //                         'branch_id'                  => $initialReport->branch_id,
    //                         'recipe_id'                  => $recipeId,
    //                         'raw_material_id'            => $ingredientReport['ingredients_id'],
    //                         'initial_bakerreport_id'     => $initialReport->id,
    //                         'branch_recipe_id'           => $initialReport->branch_recipe_id,
    //                         'quantity_used'              => $ingredientReport['quantity'],
    //                         'price_per_gram'             => 0,
    //                         'total_cost'                 => 0,
    //                         'status'                     => 'missing_stock',
    //                         'kilo'                       => $initialReport->kilo,
    //                     ]);
    //                 }

    //                 // 🧾 Update ingredient inventory total
    //                 $ingredientInvetory = BranchRawMaterialsReport::where('ingredients_id', $ingredientReport['ingredients_id'])
    //                         ->where('branch_id', $validatedData['branch_id'])
    //                         ->first();

    //                 if ($ingredientInvetory) {
    //                     $ingredientInvetory->total_quantity -= $ingredientReport['quantity'];
    //                     $ingredientInvetory->save();
    //                 }
    //             }
    //         }
    //     }

    //     // Get branch_id form the first report (assuming all reports have the same branch_id)
    //     $branch_id = $request->reports[0]['branch_id'];

    //     foreach ($request->employee_in_shift as $shift) {
    //         IncentiveEmployeeReports::create([
    //             'branch_id'              => $branch_id,
    //             'employee_id'            => $shift['employee_id'],
    //             'number_of_employees'    => $request->total_employees,
    //             'designation'            => $shift['designation'],
    //             'shift_status'           => $shift['shift_status']
    //         ]);
    //     }

    //     return response()->json([
    //         'status'     => 'success',
    //         'message'    => 'Reports stored successfully',
    //     ], 201);
    // }


    public function fetchDoughReports($branchId)
    {
        // $user = Auth::user();
        // $branch_id = $user->branch_employee->branch_id;

        $reports = InitialBakerreports::pendingDoughReports()->where('branch_id', $branchId)->with(['branch', 'user', 'branchRecipe', 'breadBakersReports'])->orderBy('created_at', 'desc')->get();

        // Return the response as JSON
        return response()->json($reports);
    }

    // public function confirmReport(Request $request, $id)
    // {
    //     $initialReport = InitialBakerreports::with('ingredientBakersReports', 'breadBakersReports.bread')->findOrFail($id);

    //     if (strtolower($initialReport->status) === 'pending' && strtolower($initialReport->recipe_category) === 'dough') {

    //         foreach ($initialReport->ingredientBakersReports as $ingredientReport) {
    //             $ingredientInventory = BranchRawMaterialsReport::where('ingredients_id', $ingredientReport->ingredients_id)
    //                 ->where('branch_id', $initialReport->branch_id)
    //                 ->first();

    //             if ($ingredientInventory) {
    //                 $ingredientInventory->total_quantity -= $ingredientReport->quantity;
    //                 $ingredientInventory->save();
    //             }
    //         }

    //         foreach ($initialReport->breadBakersReports as $breadReport) {
    //             BreadProductionReport::create([
    //                 'branch_id'                  => $initialReport->branch_id,
    //                 'user_id'                    => $initialReport->user_id,
    //                 'branch_recipe_id'           => $initialReport->branch_recipe_id,
    //                 'initial_bakerreports_id'    => $initialReport->id,
    //                 'bread_id'                   => $breadReport->bread_id,
    //                 'bread_new_production'       => $breadReport->bread_production,
    //             ]);
    //              // Update BranchProduct model
    //         $branchProduct = BranchProduct::where('branches_id', $initialReport->branch_id)
    //         ->where('product_id', $breadReport->bread_id)
    //         ->first();

    //     if ($branchProduct) {
    //         $existingTotalQuantity = $branchProduct->total_quantity;

    //         $branchProduct->new_production = $breadReport->bread_production;
    //         $branchProduct->total_quantity = $existingTotalQuantity + $branchProduct->new_production;
    //         $branchProduct->save();
    //     }
    //         }

    //         $initialReport->status = 'confirmed';
    //         $initialReport->save();

    //         return response()->json(['message' => 'Report confirmed and inventory updated successfully'], 200);
    //     }

    //     return response()->json(['message' => 'Invalid report or status'], 400);
    // }

    // public function confirmReport(Request $request, $id)
    // {
    //     // Wrap everything in a transaction to avoid race conditions
    //     return DB::transaction(function () use ($id) {

    //         $initialReport = InitialBakerreports::with('ingredientBakersReports', 'breadBakersReports.bread')
    //             ->findOrFail($id);

    //         if (strtolower($initialReport->status) !== 'pending' ||
    //             strtolower($initialReport->recipe_category) !== 'dough') {
    //             return response()->json(['message' => 'Invalid report or status'], 400);
    //         }

    //         $totalCostsResponse = []; // Collect per-ingredient totals
    //         $grandTotal = 0;

    //         foreach ($initialReport->ingredientBakersReports as $ingredientReport) {
    //             $remainingQtyToDeduct = $ingredientReport->quantity;
    //             $ingredientTotalCost = 0;
    //             $stockFound = false;

    //             // Get stocks (FIFO) and lock rows for update
    //             $branchStocks = BranchRmStocks::where('raw_material_id', $ingredientReport->ingredients_id)
    //                 ->where('branch_id', $initialReport->branch_id)
    //                 ->where('quantity', '>', 0)
    //                 ->orderBy('created_at', 'asc')
    //                 ->lockForUpdate()
    //                 ->get();

    //             foreach ($branchStocks as $stock) {
    //                 if ($remainingQtyToDeduct <= 0) break;

    //                 $stockFound = true;

    //                 // Deduct as much as possible from this stock batch
    //                 $deductQty = min($remainingQtyToDeduct, $stock->quantity);

    //                 // Calculate cost
    //                 $unitPrice = $stock->price_per_gram ?? 0;
    //                 $cost = $deductQty * $unitPrice;

    //                 // Update stock record
    //                 $stock->quantity = max(0, $stock->quantity - $deductQty);
    //                 $stock->save();

    //                 // Create a recipe_cost entry per batch deducted
    //                 RecipeCost::create([
    //                     'branch_rm_stock_id'     => $stock->id,
    //                     'branch_id'              => $initialReport->branch_id,
    //                     'recipe_id'              => $initialReport->branch_recipe_id,
    //                     'raw_material_id'        => $ingredientReport->ingredients_id,
    //                     'initial_bakerreport_id' => $initialReport->id,
    //                     'branch_recipe_id'       => $initialReport->branch_recipe_id,
    //                     'quantity_used'          => $deductQty,
    //                     'price_per_gram'         => $unitPrice,
    //                     'total_cost'             => $cost,
    //                     'status'                 => 'confirmed',
    //                     'kilo'                   => $initialReport->kilo,
    //                 ]);

    //                 // Accumulate totals
    //                 $ingredientTotalCost += $cost;
    //                 $grandTotal += $cost;

    //                 // Reduce remaining quantity
    //                 $remainingQtyToDeduct -= $deductQty;
    //             }

    //             // ⚠️ If no stock found at all — create default record
    //             if (!$stockFound) {
    //                 RecipeCost::create([
    //                     'branch_rm_stock_id'     => null,
    //                     'branch_id'              => $initialReport->branch_id,
    //                     'recipe_id'              => $initialReport->branch_recipe_id,
    //                     'raw_material_id'        => $ingredientReport->ingredients_id,
    //                     'initial_bakerreport_id' => $initialReport->id,
    //                     'branch_recipe_id'       => $initialReport->branch_recipe_id,
    //                     'quantity_used'          => $ingredientReport->quantity,
    //                     'price_per_gram'         => 0,
    //                     'total_cost'             => 0,
    //                     'status'                 => 'missing_stock',
    //                     'kilo'                   => $initialReport->kilo,
    //                 ]);
    //             }

    //             // Update branch inventory if exists
    //             $ingredientInventory = BranchRawMaterialsReport::where('ingredients_id', $ingredientReport->ingredients_id)
    //                 ->where('branch_id', $initialReport->branch_id)
    //                 ->first();

    //             if ($ingredientInventory) {
    //                 $ingredientInventory->total_quantity = max(0, $ingredientInventory->total_quantity - $ingredientReport->quantity);
    //                 $ingredientInventory->save();
    //             }

    //             $totalCostsResponse[$ingredientReport->ingredients_id] = [
    //                 'quantity_used'  => $ingredientReport->quantity,
    //                 'total_cost'     => round($ingredientTotalCost, 2),
    //                 'status'         => $stockFound ? 'confirmed' : 'missing_stock'
    //             ];
    //         }

    //         // 🥖 Handle bread production and update branch product
    //         foreach ($initialReport->breadBakersReports as $breadReport) {
    //             BreadProductionReport::create([
    //                 'branch_id'                  => $initialReport->branch_id,
    //                 'user_id'                    => $initialReport->user_id,
    //                 'branch_recipe_id'           => $initialReport->branch_recipe_id,
    //                 'initial_bakerreports_id'    => $initialReport->id,
    //                 'bread_id'                   => $breadReport->bread_id,
    //                 'bread_new_production'       => $breadReport->bread_production,
    //             ]);

    //             $branchProduct = BranchProduct::where('branches_id', $initialReport->branch_id)
    //                 ->where('product_id', $breadReport->bread_id)
    //                 ->first();

    //             if ($branchProduct) {
    //                 $branchProduct->new_production = $breadReport->bread_production;
    //                 $branchProduct->total_quantity += $breadReport->bread_production;
    //                 $branchProduct->save();
    //             }
    //         }

    //         // ✅ Finalize
    //         $initialReport->status = 'confirmed';
    //         $initialReport->save();

    //         return response()->json([
    //             'message'          => 'Report confirmed successfully. Stocks deducted and costs recorded.',
    //             'ingredient_costs' => $totalCostsResponse,
    //             'grand_total'      => round($grandTotal, 2),
    //         ], 200);
    //     });
    // }

    public function confirmReport(Request $request, $id)
    {
        // Wrap everything in a transaction to avoid race conditions
        return DB::transaction(function () use ($id) {

            $initialReport = InitialBakerreports::with('ingredientBakersReports', 'breadBakersReports.bread')
                                ->findOrFail($id);

            if (strtolower($initialReport->status) !== 'pending' ||
                strtolower($initialReport->recipe_category) !== 'dough') {
                return response()->json(['message' => 'Invalid report or status'], 400);
            }

            $totalCostResponse = []; // Collect per-ingredient totals
            $grandTotal = 0;

            // ✅ Get the actual recipe_id from branch_recipes
            $branchRecipe = BranchRecipe::find($initialReport->branch_recipe_id);
            $recipeId = $branchRecipe->recipe_id;

            foreach ($initialReport->ingredientBakersReports as $ingredientReport) {
                $remainingQtyToDeduct = $ingredientReport->quantity;
                $ingredientTotalCost = 0;
                $stockFound = false;

                // Get stocks (FIFO) and lock rows for update
                $branchStocks = BranchRmStocks::where('raw_material_id', $ingredientReport->ingredients_id)
                                ->where('branch_id', $initialReport->branch_id)
                                ->where('quantity', '>', 0)
                                ->orderBy('created_at', 'asc')
                                ->lockForUpdate()
                                ->get();

                foreach ($branchStocks as $stock) {
                    if ($remainingQtyToDeduct <= 0) break;

                    $stockFound = true;

                    // Deduct as much as possible from this stock batch
                    $deductQty = min($remainingQtyToDeduct, $stock->quantity);

                    // Calculate cost
                    $unitPrice = $stock->price_per_gram ?? 0;
                    $cost = $deductQty * $unitPrice;

                    // Update stock record
                    $stock->quantity = max(0, $stock->quantity - $deductQty);
                    $stock->save();

                    // Create a recipe_cost entry per batch deducted
                    RecipeCost::create([
                        'branch_rm_stock_id'         => $stock->id,
                        'user_id'                    => $initialReport->user_id,
                        'branch_id'                  => $initialReport->branch_id,
                        'recipe_id'                  => $recipeId,
                        'recipe_category'            => $initialReport->recipe_category,
                        'raw_material_id'            => $ingredientReport->ingredients_id,
                        'initial_bakerreport_id'     => $initialReport->id,
                        'branch_recipe_id'           => $initialReport->branch_recipe_id,
                        'quantity_used'              => $deductQty,
                        'price_per_gram'             => $unitPrice,
                        'total_cost'                 => $cost,
                        'status'                     => 'confirmed',
                        'kilo'                       => $initialReport->kilo,
                    ]);

                    // Accumulate totals
                    $ingredientTotalCost += $cost;
                    $grandTotal += $cost;

                    // Reduce remaining quantity
                    $remainingQtyToDeduct -= $deductQty;
                }

                 // ⚠️ If no stock found at all — create default record
                 if (!$stockFound) {
                    RecipeCost::create([
                        'branch_rm_stock_id'         => null,
                        'user_id'                    => $initialReport->user_id,
                        'branch_id'                  => $initialReport->branch_id,
                        'recipe_id'                  => $recipeId,
                        'recipe_category'            => $initialReport->recipe_category,
                        'raw_material_id'            => $ingredientReport->ingredients_id,
                        'initial_bakerreport_id'     => $initialReport->id,
                        'branch_recipe_id'           => $initialReport->branch_recipe_id,
                        'quantity_used'              => $ingredientReport->quantity,
                        'price_per_gram'             => 0,
                        'total_cost'                 => 0,
                        'status'                     => 'missing_stock',
                        'kilo'                       => $initialReport->kilo,
                    ]);
                 }

                 // Update branch inventory if exist
                 $ingredientInventory = BranchRawMaterialsReport::where('ingredients_id', $ingredientReport->ingredients_id)
                                        ->where('branch_id', $initialReport->branch_id)
                                        ->first();

                if ($ingredientInventory) {
                    $ingredientInventory->total_quantity = max(0, $ingredientInventory->total_quantity - $ingredientReport->quantity);
                    $ingredientInventory->save();
                }

                $totalCostResponse[$ingredientReport->ingredients_id] = [
                    'quantity_used' => $ingredientReport->quantity,
                    'total_cost' => round($ingredientTotalCost, 2),
                    'status' => $stockFound ? 'confirmed' : 'missing_stock'
                ];
            }

            // 🥖 Handle bread production and update branch product
            foreach ($initialReport->breadBakersReports as $breadReport) {
                BreadProductionReport::create([
                    'branch_id'                  => $initialReport->branch_id,
                    'user_id'                    => $initialReport->user_id,
                    'branch_recipe_id'           => $initialReport->branch_recipe_id,
                    'initial_bakerreports_id'    => $initialReport->id,
                    'bread_id'                   => $breadReport->bread_id,
                    'bread_new_production'       => $breadReport->bread_production
                ]);

                $branchProduct = BranchProduct::where('branches_id', $initialReport->branch_id)
                                    ->where('product_id', $breadReport->bread_id)
                                    ->first();

                if ($branchProduct) {
                    $branchProduct->new_production = $breadReport->bread_production;
                    $branchProduct->total_quantity += $breadReport->bread_production;
                    $branchProduct->save();
                }
            }

            // ✅ Finalize
            $initialReport->status = 'confirmed';
            $initialReport->save();

            return response()->json([
                'message'            => 'Report confirmed successfully. Stocks deducted and costs recorded.',
                'ingredient_costs'   => $totalCostResponse,
                'grand_total'        => round($grandTotal, 2),
            ], 200);
        });
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
