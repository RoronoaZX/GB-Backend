<?php

namespace App\Http\Controllers;

use App\Models\BranchRawMaterialsReport;
use App\Models\RawMaterial;
use App\Models\WarehouseRawMaterialsReport;
use Illuminate\Http\Request;

class RawMaterialController extends Controller
{
    public function index()
    {

        $raw_materials = RawMaterial::orderBy('created_at', 'desc')->get();
        return  $raw_materials;
    }

    public function fetchrawMaterialsBranchWarehouse(Request $request, $id)
    {
        $designation = strtolower($request->input('designation'));

        if ($designation === 'branch') {
            $reports = BranchRawMaterialsReport::where('branch_id', $id)
                ->with(['ingredients'])
                ->get();

            $reports->each(function ($report) use ($id) {
                $fifoStock = \App\Models\BranchRmStocks::where('branch_id', $id)
                    ->where('raw_material_id', $report->ingredients_id)
                    ->where('quantity', '>', 0)
                    ->with('deliveryUnit') // Assuming relationship exists
                    ->orderBy('created_at', 'asc')
                    ->first();
                
                if ($fifoStock) {
                    $report->fifo_price = (float)$fifoStock->price_per_gram;
                    
                    // Manually fetch if relationship isn't defined or to be safe
                    $unit = \Illuminate\Support\Facades\DB::table('delivery_stocks_units')
                        ->where('id', $fifoStock->delivery_su_id)
                        ->first();
                    
                    if ($unit) {
                        $report->fifo_category = $unit->category;
                        $report->fifo_kilo = (float)$unit->kilo;
                        $report->fifo_pcs = (float)$unit->pcs;
                        $report->fifo_price_per_unit = (float)$unit->price_per_unit;
                    }
                } else {
                    $report->fifo_price = 0;
                }
            });

            // Map to a common structure for the frontend
            $data = $reports->map(function ($report) {
                $material = $report->ingredients;
                if (!$material) return null;
                $material->available_at_source = $report->total_quantity;
                $material->fifo_price = $report->fifo_price;
                $material->fifo_category = $report->fifo_category ?? null;
                $material->fifo_kilo = $report->fifo_kilo ?? null;
                $material->fifo_pcs = $report->fifo_pcs ?? null;
                $material->fifo_price_per_unit = $report->fifo_price_per_unit ?? null;
                return $material;
            })->filter()->values();

        } elseif ($designation === 'warehouse') {
            $reports = WarehouseRawMaterialsReport::where('warehouse_id', $id)
                ->with(['rawMaterials'])
                ->get();

            $reports->each(function ($report) use ($id) {
                $fifoStock = \App\Models\WarehouseRmStocks::where('warehouse_id', $id)
                    ->where('raw_material_id', $report->raw_material_id)
                    ->where('total_grams', '>', 0)
                    ->orderBy('created_at', 'asc')
                    ->first();
                
                if ($fifoStock) {
                    $report->fifo_price = (float)$fifoStock->price_per_gram;
                    
                    $unit = \Illuminate\Support\Facades\DB::table('delivery_stocks_units')
                        ->where('id', $fifoStock->delivery_su_id)
                        ->first();
                    
                    if ($unit) {
                        $report->fifo_category = $unit->category;
                        $report->fifo_kilo = (float)$unit->kilo;
                        $report->fifo_pcs = (float)$unit->pcs;
                        $report->fifo_price_per_unit = (float)$unit->price_per_unit;
                    }
                } else {
                    $report->fifo_price = 0;
                }
            });

            $data = $reports->map(function ($report) {
                $material = $report->rawMaterials;
                if (!$material) return null;
                $material->available_at_source = $report->total_quantity;
                $material->fifo_price = $report->fifo_price;
                $material->fifo_category = $report->fifo_category ?? null;
                $material->fifo_kilo = $report->fifo_kilo ?? null;
                $material->fifo_pcs = $report->fifo_pcs ?? null;
                $material->fifo_price_per_unit = $report->fifo_price_per_unit ?? null;
                return $material;
            })->filter()->values();

        } else {
            return response()->json(['message' => 'Invalid designation'], 400);
        }

        return response()->json([
            'message'    => 'Raw materials fetched successfully',
            'data'       => $data,
        ]);
    }

    public function searchRawMaterials(Request $request)
    {
        $keyword = $request->input('keyword');

        $request->validate([
            'keyword' => 'required|string|max:255'
        ]);

        $results = RawMaterial::search($keyword)->get();

        return response()->json($results);

    }

    public function store(Request $request)
    {
        $validateData = $request->validate([
            'name'                => 'required|unique:raw_materials,name',
            'code'                => 'required|unique:raw_materials,code',
            'category'            => 'required',
            'unit'                => 'required',
            'delivery_unit'       => 'nullable|string',
            'unit_weight'         => 'nullable|numeric',
            'unit_pcs'            => 'nullable|integer',
            'supplier_lead_time'  => 'nullable|integer',
        ]);

        $rawMaterials = RawMaterial::create($validateData);

        return response()->json([
            'message'        => 'Raw Materials saved successfully',
            'rawMaterials'   => $rawMaterials
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $raw_material = RawMaterial::find($id);

        if (!$raw_material) {
            return response()->json([
                'message' => 'Raw material not found'
            ], 404);
        }

        $validateData = $request->validate([
            'name'                => 'required|unique:raw_materials,name',
            'code'                => 'required|unique:raw_materials,code',
            'category'            => 'required',
            'unit'                => 'required',
            'delivery_unit'       => 'nullable|string',
            'unit_weight'         => 'nullable|numeric',
            'unit_pcs'            => 'nullable|integer',
            'supplier_lead_time'  => 'nullable|integer',
        ]);

        $raw_material->update($validateData);
        $updated_raw_material = $raw_material->fresh();
        return response()->json($updated_raw_material);
    }

    public function destroy($id)
    {
        $raw_materials = RawMaterial::find($id);
        if (!$raw_materials) {
            return response()->json([
                'message' => 'Raw materials not found'
            ], 404);
        }

        $raw_materials->delete();
        return response()->json([
            'message' => 'raw material deleted successfully'
        ], 200);
    }

    public function fetchRawMaterialsIngredients()
    {
        $ingredients = RawMaterial::where('category','ingredients')->get();
        return response()->json($ingredients);
    }

}
