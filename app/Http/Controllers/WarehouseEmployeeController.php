<?php

namespace App\Http\Controllers;

use App\Models\WarehouseEmployee;
use Illuminate\Http\Request;

class WarehouseEmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'time_shift' => 'required|date_format:h:i A',
        ]);

        $warehouseEmployee = WarehouseEmployee::create([
            'warehouse_id' => $request->warehouse_id,
            'employee_id' => $request->employee_id,
            'time_shift' => date('H:i:s', strtotime( $request->time_shift))
        ]);

        return response()->json([
            'message' => 'Warehouse employee designation created successfully.',
            'warehouseEmployee' => $warehouseEmployee
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\WarehouseEmployee  $warehouseEmployee
     * @return \Illuminate\Http\Response
     */
    public function show(WarehouseEmployee $warehouseEmployee)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\WarehouseEmployee  $warehouseEmployee
     * @return \Illuminate\Http\Response
     */
    public function edit(WarehouseEmployee $warehouseEmployee)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\WarehouseEmployee  $warehouseEmployee
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, WarehouseEmployee $warehouseEmployee)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\WarehouseEmployee  $warehouseEmployee
     * @return \Illuminate\Http\Response
     */
    public function destroy(WarehouseEmployee $warehouseEmployee)
    {
        //
    }
}
