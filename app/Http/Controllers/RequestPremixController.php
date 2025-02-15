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
                    'status' => $req['status'],
                    'changed_by' => $req['employee_id'],
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
