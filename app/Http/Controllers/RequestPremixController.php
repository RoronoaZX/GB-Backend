<?php

namespace App\Http\Controllers;

use App\Models\BranchPremix;
use App\Models\BranchRawMaterialsReport;
use App\Models\RequestPremix;
use App\Models\RequestPremixesHistory;
use App\Models\WarehouseRawMaterialsReport;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
// use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RequestPremixController extends Controller
{
    /**
     * Display a listing of the resource.
     */

     public function getBranchEmployeePremix(Request $request, $branchId, $employeeId)
    {
        try {
            // ✅ Pagination and filters
            $perPage     = $request->input('per_page', 10);
            $search      = $request->input('search');
            $premixName  = $request->query('name');

            // ✅ Base query with relationships
            $query = RequestPremix::with([
                'branchPremix',
                'employee',
                'warehouse',
                'history' => function ($q) {
                    $q->select('id', 'request_premixes_id', 'changed_by', 'status', 'updated_at')
                      ->with('employee');
                }
            ])
            ->whereHas('branchPremix', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->where('employee_id', $employeeId);

            // ✅ Optional name filter
            if ($premixName) {
                $query->where('name', 'like', "%{$premixName}%");
            }

            // ✅ Optional search filter
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('status', 'like', "%{$search}%");
                });
            }

            // ✅ Order latest first
            $query->latest();

            // ✅ Apply pagination
            $premixes = $query->paginate($perPage);

            // ✅ Structured JSON response (consistent format)
            return response()->json([
                'success'    => true,
                'data'       => $premixes->map(function ($premix) {
                    return [
                        'id'             => $premix->id,
                        'name'           => $premix->name,
                        'status'         => $premix->status,
                        'quantity'       => $premix->quantity,
                        'remarks'        => $premix->remarks,
                        'branch_premix'  => $premix->branchPremix,
                        'employee'       => $premix->employee,
                        'warehouse'      => $premix->warehouse,
                        'history'        => $premix->history->map(function ($history) {
                            return [
                                'id'             => $history->id,
                                'changed_by'     => $history->changed_by,
                                'status'         => $history->status,
                                'employee'       => $history->employee,
                                'updated_at'     => $history->updated_at
                            ];
                        }),
                        'created_at'     => $premix->created_at,
                        'updated_at'     => $premix->updated_at,
                    ];
                }),
                'pagination' => [
                    'total'          => $premixes->total(),
                    'per_page'       => $premixes->perPage(),
                    'current_page'   => $premixes->currentPage(),
                    'last_page'      => $premixes->lastPage(),
                    'from'           => $premixes->firstItem(),
                    'to'             => $premixes->lastItem()
                ]
                ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message'    => 'Failed to fetch branch premix.',
                'error'      => $e->getMessage(),
            ], 500);
        }
    }

    public function getBranchPremix($branchId)
    {
        $page        = request()->get('page', 1);
        $perPage     = request()->get('per_page', 5);
        $search      = request()->query('search', '');

        $query       = RequestPremix::whereHas('branchPremix', function ($q) use ($branchId) {
                            $q->where('branch_id', $branchId);
                        })
                        ->with([
                            'branchPremix',
                            'employee',
                            'warehouse',
                            'history' => function ($q) {
                                $q->select('id', 'request_premixes_id', 'changed_by', 'status', 'updated_at')
                                    ->with('employee');
                            }
                        ])
                        ->orderBy('updated_at', 'desc');

        // ✅ Apply filter if there's a search term
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%");
                // Add more fields if needed
            });
        }

        // Return all data if perPage is 0 (no pagination)
        if ($perPage == 0) {
            $data = $query->get();
            return response()->json([
                'data'           => $data,
                'total'          => $data->count(),
                'per_page'       => $data->count(),
                'current_page'   => 1,
                'last_page'      => 1
            ]);
        }

        // ✅ Use built-in paginate method for server-side pagination
        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($paginated);
    }


    public function getPendingPremix($warehouseId, Request $request)
    {
         // Set category to 'pending' by default, if not provided
         $status         = $request->query('status', 'pending');

         $pendingPremix  = RequestPremix::where('warehouse_id', $warehouseId)
                                ->where('status', $status)
                                ->with('branchPremix', 'employee')
                                ->latest()
                                ->get();

        return response()->json($pendingPremix);
    }

    public function confirmPremix(Request $request)
    {
        $request->validate([
            "request_premixes_id"    => "required|exists:request_premixes,id",
            "branch_premix_id"       => "required|exists:branch_premixes,id",
            "employee_id"            => "required|exists:employees,id",
            "status"                 => "required|string",
            "quantity"               => "required|numeric|min:1",
            "warehouse_id"           => "required|exists:warehouses,id",
            "notes"                  => "nullable|string",
        ]);

        // Retrieve the request premix entry
        $requestPremix = RequestPremix::findOrFail($request->request_premixes_id);

        // Ensure it's still pending before confirming
        if ($requestPremix->status !== 'pending') {
            return response()->json(['message' => 'This premix request is not pending.'], 400);
        }

        // Update status in request_premix table
        $requestPremix->update([
            'status' => 'confirmed',
        ]);

        // Create a new entry in premix_history
        $premixHistory = RequestPremixesHistory::create([
            "request_premixes_id"    => $requestPremix->id,
            "branch_premix_id"       => $request->branch_premix_id, // Direct assignment
            "changed_by"             => $request->employee_id,
            "status"                 => "confirmed",
            "quantity"               => $request->quantity,
            "warehouse_id"           => $request->warehouse_id,
            "notes"                  => $request->notes,
        ]);

        return response()->json([
            "message"            => "Premix request confirmed successfully.",
            "premix_history"     => $premixHistory,
        ]);
    }

    public function getConfirmReports($warehouseId)
    {
        // Retrieve all confirmed premix requests for the specified warehouse
        $confirmedPremixes = RequestPremix::where('status', 'confirmed')
                                ->where('warehouse_id', $warehouseId) // Filter by warehouse
                                ->with([
                                    'branchPremix',
                                    'employee',
                                    'warehouse',
                                    'history' => function ($query) { // Fetch only confirmed history records
                                        $query->where('status', 'confirmed')
                                            ->select('id', 'request_premixes_id', 'changed_by', 'status', 'updated_at')
                                            ->with('employee'); // Include employee who changed the status
                                    }
                                ])
                                ->orderBy('updated_at', 'desc') // Sort by latest update
                                ->get();

        return response()->json($confirmedPremixes);
    }

    public function processPremix(Request $request)
    {
        $request->validate([
            "request_premixes_id"    => "required|exists:request_premixes,id",
            "branch_premix_id"       => "required|exists:branch_premixes,id",
            "employee_id"            => "required|exists:employees,id",
            "status"                 => "required|string",
            "quantity"               => "required|numeric|min:1",
            "warehouse_id"           => "required|exists:warehouses,id",
            "notes"                  => "nullable|string",
        ]);

        // Retrieve the request premix entry
        $requestPremix = RequestPremix::findOrFail($request->request_premixes_id);

        // Ensure it's still pending before confirming
        if ($requestPremix->status !== 'confirmed') {
            return response()->json(['message' => 'This premix request is not confirmed.'], 400);
        }

        // Update status in request_premix table
        $requestPremix->update([
            'status' => 'process',
        ]);

        // Create a new entry in premix_history
        $premixHistory = RequestPremixesHistory::create([
            "request_premixes_id"    => $requestPremix->id,
            "branch_premix_id"       => $request->branch_premix_id, // Direct assignment
            "changed_by"             => $request->employee_id,
            "status"                 => "process",
            "quantity"               => $request->quantity,
            "warehouse_id"           => $request->warehouse_id,
            "notes"                  => $request->notes,
        ]);

        return response()->json([
            "message" => "Premix request process successfully."
            // "premix_history" => $premixHistory,
        ], 200);
    }

    public function getProcessPremix($warehouseId)
    {
        // Retrieve all confirmed premix requests for the specified warehouse
        $processedPremixes = RequestPremix::where('status', 'process')
                                ->where('warehouse_id', $warehouseId) // Filter by warehouse
                                ->with([
                                    'branchPremix',
                                    'employee',
                                    'warehouse',
                                    'history' => function ($query) { // Fetch only confirmed history records
                                        $query->where('status', 'process')
                                            ->select('id', 'request_premixes_id', 'changed_by', 'status', 'updated_at')
                                            ->with('employee'); // Include employee who changed the status
                                    }
                                ])
                                ->orderBy('updated_at', 'desc') // Sort by latest update
                                ->get();

        return response()->json($processedPremixes);
    }

    public function completedPremix(Request $request)
    {
        $request->validate([
            "request_premixes_id"            => "required|exists:request_premixes,id",
            "branch_premix_id"               => "required|exists:branch_premixes,id",
            "employee_id"                    => "required|exists:employees,id",
            "ingredients"                    => "required|array",
            "ingredients.*.ingredient_id"    => "required",
            "ingredients.*.quantity"         => "required|numeric|min:0",
            "status"                         => "required|string",
            "quantity"                       => "required|numeric|min:1",
            "warehouse_id"                   => "required|exists:warehouses,id",
            "notes"                          => "nullable|string",
        ]);

        // Retrieve the request premix entry
        $requestPremix = RequestPremix::findOrFail($request->request_premixes_id);

        // Ensure it's still pending before confirming
        if ($requestPremix->status !== 'process') {
            return response()->json(['message' => 'This premix request is not process.'], 400);
        }

        // Update status in request_premix table
        $requestPremix->update([
            'status' => 'completed',
        ]);

        // Create a new entry in premix_history
        $premixHistory = RequestPremixesHistory::create([
            "request_premixes_id"    => $requestPremix->id,
            "branch_premix_id"       => $request->branch_premix_id, // Direct assignment
            "changed_by"             => $request->employee_id,
            "status"                 => "completed",
            "quantity"               => $request->quantity,
            "warehouse_id"           => $request->warehouse_id,
            "notes"                  => $request->notes,
        ]);

        // Deduct ingredient quantities from warehouse_ingredients
    foreach ($request->ingredients as $ingredientData) {
        $warehouseIngredient = WarehouseRawMaterialsReport::where('warehouse_id', $request->warehouse_id)
            ->where('raw_material_id', $ingredientData['ingredient_id'])
            ->first();

        if (!$warehouseIngredient) {
            return response()->json([
                "message"        => "Ingredient ID {$ingredientData['ingredient_id']} not found in this warehouse."
            ], 400);
        }

        // Ensure there's enough stock
        if ($warehouseIngredient->total_quantity < $ingredientData['quantity']) {
            return response()->json([
                "message"        => "Insufficient stock for Ingredient ID {$ingredientData['ingredient_id']}."
            ], 400);
        }

        // Deduct the quantity
        $warehouseIngredient->decrement('total_quantity', $ingredientData['quantity']);
    }

        return response()->json([
            "message"            => "Premix request completed successfully.",
            "premix_history"     => $premixHistory,
        ]);
    }

    public function getcompletedPremix($warehouseId)
    {
        // Retrieve all confirmed premix requests for the specified warehouse
        $completedPremixes = RequestPremix::where('status', 'completed')
            ->where('warehouse_id', $warehouseId) // Filter by warehouse
            ->with([
                'branchPremix',
                'employee',
                'warehouse',
                'history' => function ($query) { // Fetch only confirmed history records
                    $query->where('status', 'completed')
                        ->select('id', 'request_premixes_id', 'changed_by', 'status', 'updated_at')
                        ->with('employee'); // Include employee who changed the status
                }
            ])
            ->orderBy('updated_at', 'desc') // Sort by latest update
            ->get();

        return response()->json($completedPremixes);
    }

    public function toDeliverPremix(Request $request)
    {
        $request->validate([
            "request_premixes_id"    => "required|exists:request_premixes,id",
            "branch_premix_id"       => "required|exists:branch_premixes,id",
            "employee_id"            => "required|exists:employees,id",
            "status"                 => "required|string",
            "quantity"               => "required|numeric|min:1",
            "warehouse_id"           => "required|exists:warehouses,id",
            "notes"                  => "nullable|string",
        ]);

        // Retrieve the request premix entry
        $requestPremix = RequestPremix::findOrFail($request->request_premixes_id);

        // Ensure it's still pending before confirming
        if ($requestPremix->status !== 'completed') {
            return response()->json(['message' => 'This premix request is not completed.'], 400);
        }

        // Update status in request_premix table
        $requestPremix->update([
            'status' => 'to deliver',
        ]);

        // Create a new entry in premix_history
        $premixHistory = RequestPremixesHistory::create([
            "request_premixes_id"    => $requestPremix->id,
            "branch_premix_id"       => $request->branch_premix_id, // Direct assignment
            "changed_by"             => $request->employee_id,
            "status"                 => "to deliver",
            "quantity"               => $request->quantity,
            "warehouse_id"           => $request->warehouse_id,
            "notes"                  => $request->notes,
        ]);

        return response()->json([
            "message"            => "Premix request to deliver successfully.",
            "premix_history"     => $premixHistory,
        ]);
    }

    public function getToDeliverPremix($warehouseId)
    {
        // Retrieve all confirmed premix requests for the specified warehouse
        $toDeliverPremixes = RequestPremix::where('status', 'to deliver')
            ->where('warehouse_id', $warehouseId) // Filter by warehouse
            ->with([
                'branchPremix',
                'employee',
                'warehouse',
                'history' => function ($query) { // Fetch only confirmed history records
                    $query->where('status', 'to deliver')
                        ->select('id', 'request_premixes_id', 'changed_by', 'status', 'updated_at')
                        ->with('employee'); // Include employee who changed the status
                }
            ])
            ->orderBy('updated_at', 'desc') // Sort by latest update
            ->get();

        return response()->json($toDeliverPremixes);
    }

    public function toReceivePremix(Request $request)
    {
        $request->validate([
            "request_premixes_id"    => "required|exists:request_premixes,id",
            "branch_premix_id"       => "required|exists:branch_premixes,id",
            "employee_id"            => "required|exists:employees,id",
            "status"                 => "required|string",
            "quantity"               => "required|numeric|min:1",
            "warehouse_id"           => "required|exists:warehouses,id",
            "notes"                  => "nullable|string",
        ]);

        // Retrieve the request premix entry
        $requestPremix = RequestPremix::findOrFail($request->request_premixes_id);

        // Ensure it's still pending before confirming
        if ($requestPremix->status !== 'to deliver') {
            return response()->json(['message' => 'This premix request is not to deliver.'], 400);
        }

        // Update status in request_premix table
        $requestPremix->update([
            'status' => 'to receive',
        ]);

        // Create a new entry in premix_history
        $premixHistory = RequestPremixesHistory::create([
            "request_premixes_id"    => $requestPremix->id,
            "branch_premix_id"       => $request->branch_premix_id, // Direct assignment
            "changed_by"             => $request->employee_id,
            "status"                 => "to receive",
            "quantity"               => $request->quantity,
            "warehouse_id"           => $request->warehouse_id,
            "notes"                  => $request->notes,
        ]);

        return response()->json([
            "message" => "Premix request to deliver successfully.",
            "premix_history" => $premixHistory,
        ]);
    }

    public function getToReceivePremix($warehouseId)
    {
        // Retrieve all confirmed premix requests for the specified warehouse
        $toReceivePremixes = RequestPremix::where('status', 'to receive')
            ->where('warehouse_id', $warehouseId) // Filter by warehouse
            ->with([
                'branchPremix',
                'employee',
                'warehouse',
                'history' => function ($query) { // Fetch only confirmed history records
                    $query->where('status', 'to receive')
                        ->select('id', 'request_premixes_id', 'changed_by', 'status', 'updated_at')
                        ->with('employee'); // Include employee who changed the status
                }
            ])
            ->orderBy('updated_at', 'desc') // Sort by latest update
            ->get();

        return response()->json($toReceivePremixes);
    }

    public function receivePremix(Request $request)
    {
        $validated = $request->validate([
            'request_premix_id'              => 'required|exists:request_premixes,id',
            'branch_premix_id'               => 'required|exists:branch_premixes,id',
            'employee_id'                    => 'required|exists:employees,id',
            'status'                         => 'required|string',
            'notes'                          => 'nullable|string',
            'quantity'                       => 'required|numeric|min:1',
            'warehouse_id'                   => 'required|exists:warehouses,id',
            'branch_id'                      => 'required|exists:branches,id',
            'ingredients'                    => 'required|array',
            'ingredients.*.ingredients_id'   => 'required|exists:raw_materials,id',
            'ingredients.*.total_quantity'   => 'required|numeric|min:0',
        ]);

        $errors = []; // Store errors for ingredients

        foreach ($validated['ingredients'] as $ingredient) {
            $existingMaterial = BranchRawMaterialsReport::where('branch_id', $validated['branch_id'])
                ->where('ingredients_id', $ingredient['ingredients_id'])
                ->first();

            if ($existingMaterial) {
                $existingMaterial->increment('total_quantity', $ingredient['total_quantity']);
            } else {
                $errors[] = "Ingredient ID {$ingredient['ingredients_id']} does not exist for Branch ID {$validated['branch_id']}";
            }
        }
        $branchPremix =  BranchPremix::where('id', $validated['branch_premix_id'])
        ->first();

        if ($branchPremix) {
            $branchPremix->increment('available_stocks', $validated['quantity']);
        }


        // If any ingredient validation fails, return an error response and stop further execution
        // if (!empty($errors)) {
        //     return response()->json(['errors' => $errors], 400);
        // }

        $requestPremix = RequestPremix::findOrFail($validated['request_premix_id']);

        if ($requestPremix->status !== 'to receive') {
            return response()->json(['message' => 'This premix request is not ready to be received.'], 400);
        }

        $requestPremix->update(['status' => 'received']);

        try {
            $receivePremixes = RequestPremixesHistory::create([
                'request_premixes_id'    => $validated['request_premix_id'],
                'branch_premix_id'       => $validated['branch_premix_id'],
                'changed_by'             => $validated['employee_id'],
                'status'                 => 'received',
                'quantity'               => $validated['quantity'],
                'warehouse_id'           => $validated['warehouse_id'],
                'notes'                  => $validated['notes'],
            ]);

            return response()->json([
                'message'            => 'Branch raw materials updated successfully',
                'receivePremixes'    => $receivePremixes
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message'    => 'Error saving premix history',
                'error'      => $e->getMessage()
            ], 500);
        }
    }

    public function getRecievePremix($warehouseId, Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 5);
        // Retrieve all receive premix requests for the specified warehouse
        $receivePremixes = RequestPremix::where('status', 'received')
            ->where('warehouse_id', $warehouseId) // Filter by warehouse
            ->with([
                'branchPremix',
                'employee',
                'warehouse',
                'history' => function ($query) { // Fetch only confirmed history records
                    $query->where('status', 'received')
                        ->select('id', 'request_premixes_id', 'changed_by', 'status', 'updated_at')
                        ->with('employee'); // Include employee who changed the status
                }
            ])
            ->orderBy('updated_at', 'desc') // Sort by latest update
            ->get();

        $paginate = new LengthAwarePaginator(
            $receivePremixes->forPage($page, $perPage)->values(),
            $receivePremixes->count(),
            $perPage,
            $page,
            ['path' => url()->current()]
        );

        return response()->json($paginate);
    }

    public function declinePremix(Request $request)
    {
        $request->validate([
            "request_premixes_id"    => "required|exists:request_premixes,id",
            "branch_premix_id"       => "required|exists:branch_premixes,id",
            "employee_id"            => "required|exists:employees,id",
            "quantity"               => "required|numeric|min:1",
            "warehouse_id"           => "required|exists:warehouses,id",
            "notes"                  => "nullable|string",
        ]);

        // Retrieve the request premix entry
        $requestPremix = RequestPremix::findOrFail($request->request_premixes_id);

        // Ensure it's still pending before confirming
        if ($requestPremix->status !== 'pending') {
            return response()->json(['message' => 'This premix request is not pending.'], 400);
        }

        // Update status in request_premix table
        $requestPremix->update([
            'status' => 'declined',
        ]);

        // Create a new entry in premix_history
        $premixHistory = RequestPremixesHistory::create([
            "request_premixes_id"    => $requestPremix->id,
            "branch_premix_id"       => $request->branch_premix_id, // Direct assignment
            "changed_by"             => $request->employee_id,
            "status"                 => "declined",
            "quantity"               => $request->quantity,
            "warehouse_id"           => $request->warehouse_id,
            "notes"                  => $request->notes,
        ]);

        return response()->json([
            "message" => "Premix request declined successfully.",
            "premix_history" => $premixHistory,
        ]);
    }

    public function getDeclineReports(Request $request,$warehouseId)
    {
        try {
            // Status defaults to "declined" if not provided
            $perPage = $request->input('per_page', 1);
            $search = $request->input('search');
            $status = $request->query('status', 'declined');

            $query = RequestPremix::with([
                'branchPremix',
                'employee',
                'warehouse',
                'history' => function ($query) {
                    // Fetch only confirmed history records
                    $query->where('status', 'declined')
                        ->select('id', 'request_premixes_id', 'changed_by', 'status', 'updated_at')
                        ->with('employee'); // Include employee who changed the status
                }
            ])
            ->where('status', $status)
            ->where('warehouse_id', $warehouseId);

            // ✅ Optional Search Filter
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('branchPremix', function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('employee', function ($subQuery) use ($search) {
                        $subQuery->where('firstname', 'like', '%' . $search . '%')
                            ->orWhere('lastname', 'like', '%' . $search . '%');
                    })
                    ->orWhere('status', 'like', '%' . $search . '%');
                });
            }

            // ✅ Sort latest update
            $query->latest();

            // ✅ Paginate results
            $declinedPremixes = $query->paginate($perPage);

            // ✅ Format and respond
            return response()->json([
                'message'    => 'Declined premix reports fetched successfully.',
                'data'       => $declinedPremixes->map(function ($premix) {
                    return [
                        'id'             => $premix->id,
                        'employee'       => $premix->employee,
                        'branch_premix'  => $premix->branchPremix,
                        'name'           => $premix->branchPremix->name,
                        'quantity'       => $premix->quantity,
                        'warehouse'      => $premix->warehouse,
                        'branch_premix'  => $premix->branchPremix,
                        'status'         => $premix->status,
                        'remarks'        => $premix->remarks,
                        'history'        => $premix->history->map(function ($history) {
                            return [
                                'id'             => $history->id,
                                'changed_by'     => $history->changed_by,
                                'status'         => $history->status,
                                'updated_at'     => $history->updated_at,
                                'employee'       => $history->employee,
                            ];
                        }),
                        'created_at'     => $premix->created_at,
                        'updated_at'     => $premix->updated_at
                    ];
                }),
                'pagination' => [
                    'total'          => $declinedPremixes->total(),
                    'per_page'       => $declinedPremixes->perPage(),
                    'current_page'   => $declinedPremixes->currentPage(),
                    'last_page'      => $declinedPremixes->lastPage(),
                    'from'           => $declinedPremixes->firstItem(),
                    'to'             => $declinedPremixes->lastItem()
                ]
                ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message'    => 'Failed to fetch deliveries',
                'error'      => $e->getMessage()
            ], 500);
        }

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data (expecting an array)
        $validator = Validator::make($request->all(), [
            'requests'                       => 'required|array',
            'requests.*.branch_premix_id'    => 'required|exists:branch_premixes,id',
            'requests.*.name'                => 'required|string',
            'requests.*.category'            => 'required|string',
            'requests.*.quantity'            => 'required|numeric|min:1',
            'requests.*.status'              => 'required|string',
            'requests.*.warehouse_id'        => 'required|exists:warehouses,id',
            'requests.*.employee_id'         => 'required|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction(); // Start a transaction to ensure atomicity

        try {
            foreach ($request->requests as $req) {
                // Create the premix request
                $premixRequest = RequestPremix::create([
                    'branch_premix_id'   => $req['branch_premix_id'],
                    'name'               => $req['name'],
                    'category'           => $req['category'],
                    'quantity'           => $req['quantity'],
                    'status'             => $req['status'],
                    'warehouse_id'       => $req['warehouse_id'],
                    'employee_id'        => $req['employee_id'],
                ]);

                // Create the request history for each entry
                RequestPremixesHistory::create([
                    'request_premixes_id'    => $premixRequest->id, // Ensure it refers to the correct premix request
                    'branch_premix_id'       => $req['branch_premix_id'],
                    'warehouse_id'           => $req['warehouse_id'],
                    'status'                 => $req['status'],
                    'changed_by'             => $req['employee_id'],
                    'quantity'               => $req['quantity'],
                    'notes'                  => 'Initial request created.',
                ]);
            }

            DB::commit(); // Commit transaction if everything is successful
            return response()->json(['message' => 'Premix request submitted successfully.'], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback changes if there's an error

            return response()->json([
                'message'    => 'Error submitting request.',
                'error'      => $e->getMessage()
            ], 500);
        }
    }


}
