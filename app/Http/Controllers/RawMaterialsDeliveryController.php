<?php

namespace App\Http\Controllers;

use App\Models\BranchRmStocks;
use App\Models\DeliveryStocksUnit;
use App\Models\RawMaterial;
use App\Models\RawMaterialsDelivery;
use App\Models\WarehouseRmStocks;
use Dotenv\Repository\RepositoryInterface;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Client\ResponseSequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RawMaterialsDeliveryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
    //     try {
    //         // Fetch all deliveries with related items
    //         $deliveries = RawMaterialsDelivery::with('items.rawMaterial', 'warehouse', 'branch')->latest()->get();

    //         return response()->json([
    //             'message' => 'Deliveries fetched successfully',
    //             'data'  => $deliveries->map(function ($delivery) {
    //                 return [
    //                     'id'     => $delivery->id,
    //                     'from_id' => $delivery->from_id,
    //                     'from_designation' => $delivery->from_desisnation,
    //                     'from_name' => $delivery->from_name,
    //                     'to_id' => $delivery->to_id,
    //                     'to_designation' => $delivery->to_designation,
    //                     'to_data' => $delivery->to_data, // 👈 dynamic warehouse or branch
    //                     'remarks' => $delivery->remarks,
    //                     'status' => $delivery->status,
    //                     'items' => $delivery->items->map(function ($item) {
    //                         return [
    //                             'id' => $item->id,
    //                             'unit_type' => $item->unit_type,
    //                             'category' => $item->category,
    //                             'quantity' => $item->quantity,
    //                             'price_per_unit' => $item->price_per_unit,
    //                             'price_per_gram' => $item->price_per_gram,
    //                             'gram' => $item->gram,
    //                             'pcs' => $item->pcs,
    //                             'kilo' => $item->kilo,

    //                             // 👇 include raw material details
    //                             'raw_material' => $item->rawMaterial ? [
    //                                 'id' => $item->rawMaterial->id,
    //                                 'name' => $item->rawMaterial->name,
    //                                 'code' => $item->rawMaterial->code,
    //                                 'category' => $item->rawMaterial->category,
    //                                 'unit' => $item->rawMaterial->unit,
    //                             ] : null,
    //                             ];

    //                     }),
    //                     'created_at' => $delivery->created_at,
    //                     'updated_at' => $delivery->updated_at,
    //                 ];
    //             })
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Failed to fetch deliveries',
    //             'error'   => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function index(Request $request)
    {
        try {
            // Get pagination parameters from the request, default to 5 items per page
            $perPage = $request->input('per_page', 5);
            $search = $request->input('search');

            $query = RawMaterialsDelivery::with('items.rawMaterial', 'warehouse', 'branch')->latest();

            // Apply search filter if provided
            // if ($search) {
            //     $query->whereHas('to_data', function ($q) use ($search) {
            //         $q->where('to_name', 'like', '%' . $search . '%');
            //     })->orWhere('from_name', 'like', '%' . $search . '%')
            //       ->orWhere('status', 'like', '%' . $search . '%');
            // }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('from_name', 'like', '%' . $search . '%')
                      ->orWhere('status', 'like', '%' . $search . '%')
                      ->orWhereHas('warehouse', function ($q2) use ($search) {
                        $q2->where('name', 'like', '%' . $search . '%');
                      })
                      ->orWhereHas('branch', function ($q3) use ($search) {
                        $q3->where('name', 'like', '%' . $search . '%');
                      });
                }) ;
            }

            $deliveries = $query->paginate($perPage);

            return response()->json([
                'message' => 'Deliveries fetched successfully',
                'data'  => $deliveries->map(function ($delivery) {
                    return [
                        'id'     => $delivery->id,
                        'from_id' => $delivery->from_id,
                        'from_designation' => $delivery->from_desisnation,
                        'from_name' => $delivery->from_name,
                        'to_id' => $delivery->to_id,
                        'to_designation' => $delivery->to_designation,
                        'to_data' => $delivery->to_data, // 👈 dynamic warehouse or branch
                        'remarks' => $delivery->remarks,
                        'status' => $delivery->status,
                        'items' => $delivery->items->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'unit_type' => $item->unit_type,
                                'category' => $item->category,
                                'quantity' => $item->quantity,
                                'price_per_unit' => $item->price_per_unit,
                                'price_per_gram' => $item->price_per_gram,
                                'gram' => $item->gram,
                                'pcs' => $item->pcs,
                                'kilo' => $item->kilo,

                                // 👇 include raw material details
                                'raw_material' => $item->rawMaterial ? [
                                    'id' => $item->rawMaterial->id,
                                    'name' => $item->rawMaterial->name,
                                    'code' => $item->rawMaterial->code,
                                    'category' => $item->rawMaterial->category,
                                    'unit' => $item->rawMaterial->unit,
                                ] : null,
                                ];
                        }),
                        'created_at' => $delivery->created_at,
                        'updated_at' => $delivery->updated_at,
                    ];
                }),
                'pagination' => [
                    'total'        => $deliveries->total(),
                    'per_page'     => $deliveries->perPage(),
                    'current_page' => $deliveries->currentPage(),
                    'last_page'    => $deliveries->lastPage(),
                    'from'         => $deliveries->firstItem(),
                    'to'           => $deliveries->lastItem(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch deliveries',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function fetchPendingDelivery($id,Request $request)
    {
        try {
            // Status defaults to "pending" if not privided
            $status = $request->query('status', 'pending');
            $toDesignation = $request->query('to_designation');

            $query = RawMaterialsDelivery::with(['items.rawMaterial']);

            // Filter by status + destination id
            $query->where('status', $status)
                  ->where('to_id', $id);

            // Load relation dynamically depending on designation
            if ($toDesignation === 'Warehouse') {
                $query->with('warehouse');
            } elseif ($toDesignation === 'Branch') {
                $query->with('branch');
            } else {
                // fallback = include both
                $query->with(['warehouse','branch']);
            }

            $deliveries = $query->latest()->get();

            return response()->json([
                'message' => 'Pending deliveries fetched successfully',
                'data' => $deliveries->map(function ($delivery) {
                    return [
                        'id' => $delivery->id,
                        'from_id' => $delivery->from_id,
                        'from_designation' => $delivery->from_designation,
                        'from_name' => $delivery->from_name,
                        'to_id' => $delivery->to_id,
                        'to_designation' => $delivery->to_designation,
                        'from_name' => $delivery->from_name,
                        'to_id' => $delivery->to_id,
                        'to_designation' => $delivery->to_designation,
                        'to_data' => $delivery->to_data,
                        'remarks' => $delivery->remarks,
                        'status' => $delivery->status,
                        'items' => $delivery->items->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'unit_type' => $item->unit_type,
                                'category' => $item->category,
                                'quantity' => $item->quantity,
                                'price_per_unit' => $item->price_per_unit,
                                'price_per_gram' => $item->price_per_gram,
                                'gram' => $item->gram,
                                'pcs' => $item->pcs,
                                'kilo' => $item->kilo,
                                'raw_material_id' => $item->rawMaterial ? $item->rawMaterial->id : null,
                                'raw_material' => $item->rawMaterial ? [
                                    'id' => $item->rawMaterial->id,
                                    'name' => $item->rawMaterial->name,
                                    'code' => $item->rawMaterial->code,
                                    'category' => $item->rawMaterial->category,
                                    'unit' => $item->rawMaterial->unit,
                                ] : null,

                            ];
                        }),
                        'created_at' => $delivery->created_at,
                        'updated_at' => $delivery->updated_at,
                    ];
                })
            ], 200);


        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch deliveries',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function confirmDelivery(Request $request)
    {
        try {
            // ✅ 1. Validate request
            $validated = $request->validate([
                'id'  => 'required|integer|exists:raw_materials_deliveries,id',
                'to_id' => 'required|integer',
                'to_designation' => 'required|string|in:Branch,Warehouse',
                'status' => 'required|string|in:confirmed',
                'items' => 'required|array',
                'items.*.raw_material_id' => 'required|integer|exists:raw_materials,id',
                'items.*.quantity' => 'required|numeric|min:1',
                'items.*.price_per_unit' => 'required|numeric|min:0',
                'items.*.price_per_gram' => 'required|numeric|min:0'
            ]);

            // ✅ 2. Find the delivery
            $delivery = RawMaterialsDelivery::findOrFail($validated['id']);
            $delivery->status = $validated['status'];
            $delivery->save();

            // ✅ 3. Update stocks if confirmed
            if($validated['status'] === 'confirmed') {
                foreach ($validated['items'] as $item) {
                   if ($validated['to_designation'] === 'Branch') {
                    // --- Save to Branch Stocks ---
                    BranchRmStocks::updateOrCreate(
                        [
                            'branch_id' => $validated['to_id'],
                            'raw_material_id' => $item['raw_material_id'],
                            'price_per_gram' => $item['price_per_gram'], // 🔑 uniqueness
                        ],
                        [
                            'quantity' => DB::raw('quantity + ' . $item['quantity']),
                        ]
                        );
                   } elseif ($validated['to_designation'] === 'Warehouse') {
                    // --- Save to Warehouse Stocks ---
                    WarehouseRmStocks::updateOrCreate(
                        [
                            'warehouse_id' => $validated['to_id'],
                            'raw_material_id' => $item['raw_material_id'],
                            'price_per_gram' => $item['price_per_gram'] // 🔑 uniqueness
                        ],
                        [
                            'quantity' => DB::raw('quantity + ' . $item['quantity'])
                        ]
                        );
                   }
                }
            }
            return response()->json([
                'message' => 'Delivery ' . $validated['status'] . ' successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to proccess delivery.',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    public function declineDelivery(Request $request)
    {
        try {
            // ✅ 1. Validate request
            $validated = $request->validate([
                'id' => 'required|integer|exists:raw_materials_deliveries,id',
                'remarks' => 'required|string|max:1000'
            ]);

            // ✅ 2. Find the delivery
            $delivery = RawMaterialsDelivery::findOrFail($validated['id']);

            // ✅ 3. Update status + remarks
            $delivery->status = 'declined';
            $delivery->remarks = $validated['remarks'];
            $delivery->save();

            // ✅ 4. Return success


        } catch (\Exception $e) {
            // ❌ Handle errors

            return response()->json([
                'message' => 'Failed to decline delivery.',
                'error' => $e->getMessage()
            ], 500);
        }
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
            'raw_materials_groups.*.kilo'                => 'nullable|numeric',
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
