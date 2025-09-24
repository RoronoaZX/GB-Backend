<?php

namespace App\Http\Controllers;

use App\Models\DeliveryStocksUnit;
use App\Models\RawMaterialsDelivery;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RawMaterialsDeliveryController extends Controller
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
    public function create(Request $request)
    {
        // 1️⃣ Validate the request before doing anything
        $validator = Validator::make(request()->all(), [
            'from_id'                => 'nullable|integer',
            'from_designation'       => 'nullable|string',
            'from_name'              => 'nullable|string',
            'to_id'                  => 'nullable|integer',
            'to_designation'         => 'nullable|string',
            'remarks'                => 'nullable|string',
            'status'                 => 'nullable|string',
            'raw_materials_groups'   => 'required|array',

            // Validate each item in the raw_materials_groups array
            'raw_materials_groups.*.raw_materials_id'    => 'required|exists:raw_materials,id',
            'raw_materials_groups.*.raw_materials_name'  => 'required|string',
            'raw_materials_groups.*.unit_type'           => 'required|string',
            'raw_materials_groups.*.quantity'            => 'required|numeric',
            'raw_materials_groups.*.price_per_unit'      => 'required|numeric',
            'raw_materials_groups.*.price_per_gram'      => 'required|numeric',
            'raw_materials_groups.*.pcs'                 => 'required|integer',
            'raw_materials_groups.*.kilo'                => 'required|numeric',
            'raw_materials_groups.*.gram'                => 'required|numeric',
            'raw_materials_groups.*.category'            => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }


        // 2️⃣ Wrap in transaction

        try {

            $delivery = DB::transaction(function () use ($request) {
                $rawMaterialsDelivery = RawMaterialsDelivery::create([
                    'from_id'            => $request->input('from_id'),
                    'from_designation'   => $request->input('from_designation'),
                    'from_name'          => $request->input('from_name'),
                    'to_id'              => $request->input('to_id'),
                    'to_designation'     => $request->input('to_designation'),
                    'remarks'            => $request->input('remarks'),
                    'status'             => $request->input('status', 'Pending')
                ]);

                // Create child records (raw materials groups)
                foreach ($request->input('raw_materials_groups') as $group) {
                    DeliveryStocksUnit::create([
                        'rm_delivery_id'     => $rawMaterialsDelivery->id,
                        'raw_materials_id'   => $group['raw_materials_id'],
                        'unit_type'          => $group['unit_type'],
                        'category'           => $group['category'],
                        'quantity'           => $group['quantity'],
                        'price_per_unit'     => $group['price_per_unit'],
                        'price_per_gram'     => $group['price_per_gram'],
                        'gram'               => $group['gram'],
                        'pcs'                => $group['pcs'],
                        'kilo'               => $group['kilo']
                    ]);
                }

                return $rawMaterialsDelivery;
            });

            return response()->json([
                'message' => 'Raw materials delivery created successfully',
                'data'  =>  $delivery->load('items')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => ' Failed to create raw materials delivery',
                'error'   => $e->getMessage()
                ], 500);
        }

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     */
    public function show(RawMaterialsDelivery $rawMaterialsDelivery)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RawMaterialsDelivery $rawMaterialsDelivery)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RawMaterialsDelivery $rawMaterialsDelivery)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RawMaterialsDelivery $rawMaterialsDelivery)
    {
        //
    }
}
