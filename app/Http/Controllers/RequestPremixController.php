<?php

namespace App\Http\Controllers;

use App\Models\RequestPremix;
use App\Models\RequestPremixesHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RequestPremixController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function getPendingPremix($warehouseId, Request $request)
    {
         // Set category to 'pending' by default, if not provided
         $status = $request->query('status', 'pending');

         $pendingPremix = RequestPremix::where('warehouse_id', $warehouseId)
                    ->where('status', $status)
                    ->with('branchPremix', 'employee')
                    ->get();

        return response()->json($pendingPremix);
    }

    public function confirmPremix(Request $request)
    {
        $request->validate([
            "request_premixes_id" => "required|exists:request_premixes,id",
            "branch_premix_id" => "required|exists:branch_premixes,id",
            "employee_id" => "required|exists:employees,id",
            "status" => "required|string",
            "quantity" => "required|numeric|min:1",
            "warehouse_id" => "required|exists:warehouses,id",
            "notes" => "nullable|string",
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
            "request_premixes_id" => $requestPremix->id,
            "branch_premix_id" => $request->branch_premix_id, // Direct assignment
            "changed_by" => $request->employee_id,
            "status" => "confirmed",
            "quantity" => $request->quantity,
            "warehouse_id" => $request->warehouse_id,
            "notes" => $request->notes,
        ]);

        return response()->json([
            "message" => "Premix request confirmed successfully.",
            "premix_history" => $premixHistory,
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
            "request_premixes_id" => "required|exists:request_premixes,id",
            "branch_premix_id" => "required|exists:branch_premixes,id",
            "employee_id" => "required|exists:employees,id",
            "status" => "required|string",
            "quantity" => "required|numeric|min:1",
            "warehouse_id" => "required|exists:warehouses,id",
            "notes" => "nullable|string",
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
            "request_premixes_id" => $requestPremix->id,
            "branch_premix_id" => $request->branch_premix_id, // Direct assignment
            "changed_by" => $request->employee_id,
            "status" => "process",
            "quantity" => $request->quantity,
            "warehouse_id" => $request->warehouse_id,
            "notes" => $request->notes,
        ]);

        return response()->json([
            "message" => "Premix request process successfully.",
            "premix_history" => $premixHistory,
        ]);
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

    public function declinePremix(Request $request)
    {
        $request->validate([
            "request_premixes_id" => "required|exists:request_premixes,id",
            "branch_premix_id" => "required|exists:branch_premixes,id",
            "employee_id" => "required|exists:employees,id",
            "quantity" => "required|numeric|min:1",
            "warehouse_id" => "required|exists:warehouses,id",
            "notes" => "nullable|string",
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
            "request_premixes_id" => $requestPremix->id,
            "branch_premix_id" => $request->branch_premix_id, // Direct assignment
            "changed_by" => $request->employee_id,
            "status" => "declined",
            "quantity" => $request->quantity,
            "warehouse_id" => $request->warehouse_id,
            "notes" => $request->notes,
        ]);

        return response()->json([
            "message" => "Premix request declined successfully.",
            "premix_history" => $premixHistory,
        ]);
    }

    public function getDeclineReports($warehouseId)
    {
        // Retrieve all decline premix requests for the specified warehouse
        $declinedPremixes = RequestPremix::where('status', 'declined')
        ->where('warehouse_id', $warehouseId) // Filter by warehouse
        ->with([
            'branchPremix',
            'employee',
            'warehouse',
            'history' => function ($query) { // Fetch only confirmed history records
                $query->where('status', 'declined')
                    ->select('id', 'request_premixes_id', 'changed_by', 'status', 'updated_at')
                    ->with('employee'); // Include employee who changed the status
            }
        ])
        ->orderBy('updated_at', 'desc') // Sort by latest update
        ->get();

    return response()->json($declinedPremixes);

    }
    // public function getConfirmReports($warehouseId)
    // {
    //     // Retrieve all confirmed premix requests for the specified warehouse
    //     $confirmedPremixes = RequestPremix::where('status', 'confirmed')
    //         ->where('warehouse_id', $warehouseId) // Filter by warehouse
    //         ->with(['branchPremix', 'employee', 'warehouse'])
    //         ->orderBy('updated_at', 'desc') // Use 'updated_at' instead of 'confirmed_at' (if applicable)
    //         ->get();

    //     return response()->json($confirmedPremixes);
    // }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data (expecting an array)
        $validator = Validator::make($request->all(), [
            'requests' => 'required|array',
            'requests.*.branch_premix_id' => 'required|exists:branch_premixes,id',
            'requests.*.name' => 'required|string',
            'requests.*.category' => 'required|string',
            'requests.*.quantity' => 'required|numeric|min:1',
            'requests.*.status' => 'required|string',
            'requests.*.warehouse_id' => 'required|exists:warehouses,id',
            'requests.*.employee_id' => 'required|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction(); // Start a transaction to ensure atomicity

        try {
            foreach ($request->requests as $req) {
                // Create the premix request
                $premixRequest = RequestPremix::create([
                    'branch_premix_id' => $req['branch_premix_id'],
                    'name' => $req['name'],
                    'category' => $req['category'],
                    'quantity' => $req['quantity'],
                    'status' => $req['status'],
                    'warehouse_id' => $req['warehouse_id'],
                    'employee_id' => $req['employee_id'],
                ]);

                // Create the request history for each entry
                RequestPremixesHistory::create([
                    'request_premixes_id' => $premixRequest->id, // Ensure it refers to the correct premix request
                    'branch_premix_id' => $req['branch_premix_id'],
                    'status' => $req['status'],
                    'changed_by' => $req['employee_id'],
                    'quantity' => $req['quantity'],
                    'notes' => 'Initial request created.',
                ]);
            }

            DB::commit(); // Commit transaction if everything is successful
            return response()->json(['message' => 'Premix request submitted successfully.'], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback changes if there's an error

            return response()->json([
                'message' => 'Error submitting request.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(RequestPremix $requestPremix)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RequestPremix $requestPremix)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RequestPremix $requestPremix)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RequestPremix $requestPremix)
    {
        //
    }
}
