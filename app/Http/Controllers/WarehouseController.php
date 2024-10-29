<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouse = Warehouse::orderBy('created_at', 'desc')->get();
        return  $warehouse;
    }


    public function searchWarehouse(Request $request)
    {
        $keyword = $request->input('keyword');

        $request->validate([
            'keyword' => 'required|string|max:255'
        ]);

        $results = Warehouse::search($keyword)->get();

        return response()->json($results);
    }

    public function fetchWarehouseWithEmployee()
    {
        $warehouseWithEmployee = Warehouse::with('warehouseEmployee')->orderBy('name', 'asc')->get();
        return response()->json($warehouseWithEmployee,200);
    }

    public function store(Request $request)
    {
        $validateData = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'name' => 'required||unique:warehouses',
            'location' => 'nullable',
            'phone' => 'nullable',
            'status' => 'nullable',
        ]);

        $warehouse = Warehouse::create($validateData);

        return response()->json($warehouse, 201);
    }


    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::find($id);
        if (!$warehouse) {
            return response()->json([
                'message' => 'Raw material not found'
            ], 404);
        }
        $warehouse->update($request->all());
        $updated_warehouse = $warehouse->fresh();
        return response()->json($updated_warehouse);
    }

    public function destroy($id)
    {
       $warehouse = Warehouse::find($id);
       if (!$warehouse) {
        return response()->json([
            'message' => 'Warehouse not found'
        ]);
       }
       $warehouse->delete();
       return response()->json([
        'message' => 'Warehouse deleted successfully'
       ]);
    }

}
