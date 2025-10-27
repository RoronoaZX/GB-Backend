<?php

namespace App\Http\Controllers;

use App\Models\BranchRawMaterialsReport;
use App\Models\BranchRmStocks;
use App\Models\DeliveryStocksUnit;
use App\Models\RawMaterial;
use App\Models\RawMaterialsDelivery;
use App\Models\SupplierIngredient;
use App\Models\SupplierRecord;
use App\Models\Warehouse;
use App\Models\WarehouseRawMaterialsReport;
use App\Models\WarehouseRmStocks;
use Carbon\Carbon;
use Dotenv\Repository\RepositoryInterface;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Client\ResponseSequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Contracts\Service\Attribute\Required;

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
    //

    public function index(Request $request)
    {
        try {
            // Get pagination parameters from the request, default to 5 items per page
            $perPage = $request->input('per_page', 5);
            $search = $request->input('search');

            $query = RawMaterialsDelivery::with('items.rawMaterial', 'warehouse', 'branch')->latest();

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
                'message'        => 'Deliveries fetched successfully',
                'data'           => $deliveries->map(function ($delivery) {
                    return [
                        'id'                 => $delivery->id,
                        'from_id'            => $delivery->from_id,
                        'from_designation'   => $delivery->from_designation,
                        'from_name'          => $delivery->from_name,
                        'to_id'              => $delivery->to_id,
                        'to_designation'     => $delivery->to_designation,
                        'to_data'            => $delivery->to_data, // 👈 dynamic warehouse or branch
                        'remarks'            => $delivery->remarks,
                        'status'             => $delivery->status,
                        'items'              => $delivery->items->map(function ($item) {
                            return [
                                'id'                 => $item->id,
                                'unit_type'          => $item->unit_type,
                                'category'           => $item->category,
                                'quantity'           => $item->quantity,
                                'price_per_unit'     => $item->price_per_unit,
                                'price_per_gram'     => $item->price_per_gram,
                                'gram'               => $item->gram,
                                'pcs'                => $item->pcs,
                                'kilo'               => $item->kilo,

                                // 👇 include raw material details
                                'raw_material'       => $item->rawMaterial ? [
                                    'id'                     => $item->rawMaterial->id,
                                    'name'                   => $item->rawMaterial->name,
                                    'code'                   => $item->rawMaterial->code,
                                    'category'               => $item->rawMaterial->category,
                                    'unit'                   => $item->rawMaterial->unit,
                                ] : null,
                                ];
                        }),
                        'created_at'         => $delivery->created_at,
                        'updated_at'         => $delivery->updated_at,
                    ];
                }),
                'pagination'     => [
                    'total'                  => $deliveries->total(),
                    'per_page'               => $deliveries->perPage(),
                    'current_page'           => $deliveries->currentPage(),
                    'last_page'              => $deliveries->lastPage(),
                    'from'                   => $deliveries->firstItem(),
                    'to'                     => $deliveries->lastItem(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch deliveries',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function fetchDeliveryStocksBranch($id, Request $request)
    {
        try {
            $perPage = $request->input('per_page', 5);
            $search = $request->input('search');
            $toDesignation = $request->query('to_designation');

            $query = RawMaterialsDelivery::with(['items.rawMaterial', 'employee', 'approvedBy']);

            // Filter by status + destination id
            $query->where('to_id', $id);

            // Load relation dynamically depending on designation
            if ($toDesignation === 'Branch') {
                $query->with('branch');
            }

            // ✅ Apply search filter (optional)
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('from_name', 'like', '%' . $search . '%')
                      ->orWhere('status', 'like', '%' . $search . '%');
                });
            }
            // if ($search) {
            //     $query->whereHas('items.rawMaterial', function ($q) use ($search) {
            //         $q->where('name', 'like', '%' . $search . '%');
            //     });
            // }

            // ✅ Order latest deliveries first
            $query->latest();

            // ✅ Apply pagination
            $deliveries = $query->paginate($perPage);

            return response()->json([
                'success'    => true,
                'data'       => $deliveries->map(function ($delivery) {
                    return [
                        'id'                  => $delivery->id,
                        'employee'            => $delivery->employee,
                        'approved_by'         => $delivery->approvedBy,
                        'from_id'             => $delivery->from_id,
                        'from_designation'    => $delivery->from_designation,
                        'from_name'           => $delivery->from_name,
                        'to_id'               => $delivery->to_id,
                        'to_designation'      => $delivery->to_designation,
                        'to_data'             => $delivery->to_data, // 👈 dynamic warehouse or branch
                        'remarks'             => $delivery->remarks,
                        'status'              => $delivery->status,
                        'items'               => $delivery->items->map(function ($item) {
                            return [
                                'id'                 => $item->id,
                                'unit_type'          => $item->unit_type,
                                'category'           => $item->category,
                                'quantity'           => $item->quantity,
                                'price_per_unit'     => $item->price_per_unit,
                                'price_per_gram'     => $item->price_per_gram,
                                'gram'               => $item->gram,
                                'pcs'                => $item->pcs,
                                'kilo'               => $item->kilo,
                                'raw_material_id'    => $item->rawMaterial ? $item->rawMaterial->id : null,
                                'raw_material'       => $item->rawMaterial ? [
                                    'id'                     => $item->rawMaterial->id,
                                    'name'                   => $item->rawMaterial->name,
                                    'code'                   => $item->rawMaterial->code,
                                    'category'               => $item->rawMaterial->category,
                                    'unit'                   => $item->rawMaterial->unit
                                ] : null,
                                ];
                        }),
                        'created_at'          => $delivery->created_at,
                        'updated_at'          => $delivery->updated_at,
                    ];
                }),
                'pagination' => [
                    'total'                   => $deliveries->total(),
                    'per_page'                => $deliveries->perPage(),
                    'current_page'            => $deliveries->currentPage(),
                    'last_page'               => $deliveries->lastPage(),
                    'from'                    => $deliveries->firstItem(),
                    'to'                      => $deliveries->lastItem()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch deliveries',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function fetchPendingDelivery($id, Request $request)
    {
        try {
            // Status defaults to "pending" if not privided
            $status = $request->query('status', 'pending');
            $toDesignation = $request->query('to_designation');

            $query = RawMaterialsDelivery::with(['items.rawMaterial', 'employee']);

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
                        'id'                 => $delivery->id,
                        'employee'           => $delivery->employee,
                        'from_id'            => $delivery->from_id,
                        'from_designation'   => $delivery->from_designation,
                        'from_name'          => $delivery->from_name,
                        'to_id'              => $delivery->to_id,
                        'to_designation'     => $delivery->to_designation,
                        'from_name'          => $delivery->from_name,
                        'to_id'              => $delivery->to_id,
                        'to_designation'     => $delivery->to_designation,
                        'to_data'            => $delivery->to_data,
                        'remarks'            => $delivery->remarks,
                        'status'             => $delivery->status,
                        'items'              => $delivery->items->map(function ($item) {
                            return [
                                'id'                 => $item->id,
                                'unit_type'          => $item->unit_type,
                                'category'           => $item->category,
                                'quantity'           => $item->quantity,
                                'price_per_unit'     => $item->price_per_unit,
                                'price_per_gram'     => $item->price_per_gram,
                                'gram'               => $item->gram,
                                'pcs'                => $item->pcs,
                                'kilo'               => $item->kilo,
                                'raw_material_id'    => $item->rawMaterial ? $item->rawMaterial->id : null,
                                'raw_material'       => $item->rawMaterial ? [
                                    'id'                     => $item->rawMaterial->id,
                                    'name'                   => $item->rawMaterial->name,
                                    'code'                   => $item->rawMaterial->code,
                                    'category'               => $item->rawMaterial->category,
                                    'unit'                   => $item->rawMaterial->unit,
                                ] : null,

                            ];
                        }),
                        'created_at'                 => $delivery->created_at,
                        'updated_at'                 => $delivery->updated_at,
                    ];
                })
            ], 200);


        } catch (\Exception $e) {
            return response()->json([
                'message'    => 'Failed to fetch deliveries',
                'error'      => $e->getMessage()
            ], 500);
        }
    }

    public function fetchConfirmedDelivery($id, Request $request)
    {
        try {
            // Status defaults to "confirmed" if not provided
            $perPage = $request->input('per_page', 3);
            $search = $request->input('search');
            $status = $request->query('status', 'confirmed');
            $toDesignation = $request->query('to_designation');

            $query = RawMaterialsDelivery::with(['items.rawMaterial', 'employee', 'approvedBy']);

            // Filter by status + destination id
            $query->where('status', $status)
                  ->where('to_id', $id);

            // Load relation dynammically depending on designation
            if ($toDesignation === 'Warehouse') {
                $query->with('warehouse');
            } elseif ($toDesignation === 'Branch') {
                $query->with('branch');
            } else {
                // fallback = include both
                $query->with(['warehouse','branch']);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('from_name', 'like', '%' . $search . '%')
                      ->orWhere('status', 'like', '%' . $search . '%');
                });
            }

            $query->latest();

            $deliveries = $query->paginate($perPage);

            return response()->json([
                'message'    => 'Confirmed deliveries fetched successfully',
                'data'       => $deliveries->map(function ($delivery) {
                    return [
                        'id'                 => $delivery->id,
                        'employee'           => $delivery->employee,
                        'approved_by'        => $delivery->approvedBy,
                        'from_id'            => $delivery->from_id,
                        'from_designation'   => $delivery->from_designation,
                        'from_name'          => $delivery->from_name,
                        'to_id'              => $delivery->to_id,
                        'to_designation'     => $delivery->to_designation,
                        'to_data'            => $delivery->to_data,
                        'remarks'            => $delivery->remarks,
                        'status'             => $delivery->status,
                        'items'              => $delivery->items->map(function ($item) {
                            return [
                                'id'                 => $item->id,
                                'unit_type'          => $item->unit_type,
                                'category'           => $item->category,
                                'quantity'           => $item->quantity,
                                'price_per_unit'     => $item->price_per_unit,
                                'price_per_gram'     => $item->price_per_gram,
                                'gram'               => $item->gram,
                                'pcs'                => $item->pcs,
                                'kilo'               => $item->kilo,
                                'raw_material_id'    => $item->rawMaterial ? $item->rawMaterial->id : null,
                                'raw_material'       => $item->rawMaterial ? [
                                    'id'                     => $item->rawMaterial->id,
                                    'name'                   => $item->rawMaterial->name,
                                    'code'                   => $item->rawMaterial->code,
                                    'category'               => $item->rawMaterial->category,
                                    'unit'                   => $item->rawMaterial->unit,
                                ] : null,
                                ];
                        }),
                        'created_at'         => $delivery->created_at,
                        'updated_at'         => $delivery->updated_at
                    ];
                }),
                'pagination' => [
                    'total' => $deliveries->total(),
                    'per_page' => $deliveries->perPage(),
                    'current_page' => $deliveries->currentPage(),
                    'last_page' => $deliveries->lastPage(),
                    'from' => $deliveries->firstItem(),
                    'to' => $deliveries->lastItem()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch deliveries',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function fetchDeclinedDelivery($id, Request $request)
    {
        try {
            // Status defaults to "declined" if not provided
            $perPage = $request->input('per_page', 3);
            $search = $request->input('search');
            $status = $request->query('status', 'declined');
            $toDesignation = $request->query('to_designation');

            $query = RawMaterialsDelivery::with(['items.rawMaterial', 'employee', 'approvedBy']);

            // Filter by status + destination id
            $query->where('status', $status)
                  ->where('to_id', $id);

            // Load relation dynamitically depending on designation
            if ($toDesignation === 'Warehouse') {
                $query->with('warehouse');
            }elseif ($toDesignation === 'Branch') {
                $query->with(['Warehouse', 'branch']);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('from_name', 'like', '%' . $search . '%')
                      ->orWhere('status', 'like', '%' . $search . '%');
                });
            }

            $query->latest();

            $deliveries = $query->paginate($perPage);

            return response()->json([
                'message'    => 'Confirmed deliveries fetched successfully.',
                'data'       => $deliveries->map(function ($delivery) {
                    return [
                        'id'                 => $delivery->id,
                        'employee'            => $delivery->employee,
                        'approved_by'         => $delivery->approvedBy,
                        'from_id'            => $delivery->from_id,
                        'from_designation'   => $delivery->from_designation,
                        'from_name'          => $delivery->from_name,
                        'to_id'              => $delivery->to_id,
                        'to_designation'     => $delivery->to_designation,
                        'to_data'            => $delivery->to_data,
                        'remarks'            => $delivery->remarks,
                        'status'             => $delivery->status,
                        'items'              => $delivery->items->map(function ($item) {
                            return [
                                'id'                 => $item->id,
                                'unit_type'          => $item->unit_type,
                                'category'           => $item->category,
                                'quantity'           => $item->quantity,
                                'price_per_unit'     => $item->price_per_unit,
                                'price_per_gram'     => $item->price_per_gram,
                                'gram'               => $item->gram,
                                'pcs'                => $item->pcs,
                                'kilo'               => $item->kilo,
                                'raw_material_id'    => $item->rawMaterial ? $item->rawMaterial->id : null,
                                'raw_material'       => $item->rawMaterial ? [
                                    'id'                     => $item->rawMaterial->id,
                                    'name'                   => $item->rawmaterial->name,
                                    'code'                   => $item->rawMaterial->code,
                                    'category'               => $item->rawMaterial->category,
                                    'unit'                   => $item->rawMaterial->unit
                                ] : null,
                                ];
                        }),
                        'created_at'         => $delivery->created_at,
                        'updated_at'         => $delivery->updated_at
                    ];
                }),
                'pagination' => [
                    'total'          => $deliveries->total(),
                    'per_page'       => $deliveries->perPage(),
                    'current_page'   => $deliveries->currentPage(),
                    'last_page'      => $deliveries->lastPage(),
                    'from'           => $deliveries->firstItem(),
                    'to'             => $deliveries->lastItem()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message'    => 'Failed to fetch deliveries',
                'error'      => $e->getMessage()
            ], 500);
        }
    }

    // public function confirmDelivery(Request $request)
    // {
    //     try {
    //         // ✅ 1. Validate request
    //         $validated = $request->validate([
    //             'id'                 => 'required|integer|exists:raw_materials_deliveries,id',
    //             'from_id'            => 'nullable|integer',
    //             'from_designation'   => 'required|string|in:Branch,Warehouse,Supplier',
    //             'to_id'              => 'required|integer',
    //             'to_designation'     => 'required|string|in:Branch,Warehouse',
    //             'status'             => 'required|string|in:confirmed',
    //             'items'              => 'required|array',
    //             'items.*.id'                 => 'required|integer|exists:delivery_stocks_units,id',
    //             'items.*.raw_material_id'    => 'required|integer|exists:raw_materials,id',
    //             'items.*.quantity'           => 'required|numeric|min:1',
    //             'items.*.gram'               => 'nullable|numeric|min:0',
    //             'items.*.kilo'               => 'nullable|numeric|min:0',
    //             'items.*.pcs'                => 'nullable|numeric|min:0',
    //             'items.*.price_per_unit'     => 'required|numeric|min:0',
    //             'items.*.price_per_gram'     => 'required|numeric|min:0',
    //             'items.*.total_grams'        => 'required|numeric|min:0'
    //         ]);

    //         // ✅ 2. Find the delivery
    //         $delivery = RawMaterialsDelivery::findOrFail($validated['id']);
    //         $delivery->status = $validated['status'];
    //         $delivery->save();

    //         // ✅ 3. Update stocks if confirmed
    //         if ($validated['status'] === 'confirmed') {
    //             foreach ($validated['items'] as $item) {
    //                 /**
    //                  * 🟢 Deduct FROM source
    //                  */
    //                 if ($validated['from_designation'] === 'Warehouse') {
    //                     // Deduct from WarehouseRawMaterialsReport
    //                     $report = WarehouseRawMaterialsReport::where([
    //                         'warehouse_id'       => $validated['from_id'],
    //                         'raw_material_id'    => $item['raw_material_id'],
    //                     ])->first();

    //                     if($report) {
    //                         // $report->update([
    //                         //     'total_quantity' => DB::raw('total_quantity - ' . $item['total_grams'])
    //                         // ]);
    //                         $report->decrement('total_quantity', $item['total_grams']);
    //                     } else {
    //                         // optional
    //                         return response()->json([
    //                             'message'            => 'Stock report not found for this raw material in the warehouse.',
    //                             'raw_material_id'    => $item['raw_material_id']
    //                         ], 400);
    //                     }
    //                 } elseif ($validated['from_designation'] === 'Branch') {
    //                     // Deduct from BranchRawMaterialsReport
    //                     $report = BranchRawMaterialsReport::where([
    //                         'branch_id'          => $validated['from_id'],
    //                         'ingredients_id'     => $item['raw_material_id']
    //                     ])->first();

    //                     if ($report) {
    //                         $report->update([
    //                             'total_quantity' => DB::raw('total_quantity - ' . $item['total_grams'])
    //                         ]);
    //                     }
    //                 }
    //                 // Supplier = no deduction

    //                 /**
    //                  * 🟢 Add TO destination
    //                  */
    //                 // Note that the total_grams is the quantity in grams here in BranchRmStocks
    //                 if ($validated['to_designation'] === 'Branch') {
    //                     $stock = BranchRmStocks::where([
    //                         'branch_id'          => $validated['to_id'],
    //                         'raw_material_id'    => $item['raw_material_id'],
    //                         'price_per_gram'     => $item['price_per_gram'],
    //                     ])->first();

    //                     if ($stock) {
    //                         $stock->update([
    //                             'quantity' => DB::raw('quantity + ' . $item['total_grams']),
    //                             'delivery_su_id' => $item['id'],
    //                         ]);
    //                     } else {
    //                         BranchRmStocks::create([
    //                             'branch_id'          => $validated['to_id'],
    //                             'raw_material_id'    => $item['raw_material_id'],
    //                             'price_per_gram'     => $item['price_per_gram'],
    //                             'quantity'           => $item['quantity'],
    //                             'delivery_su_id'     => $item['id'], // ✅ save link
    //                         ]);
    //                     }

    //                     // Update branch report
    //                     $report = BranchRawMaterialsReport::where([
    //                         'branch_id'          => $validated['to_id'],
    //                         'ingredients_id'     => $item['raw_material_id']
    //                     ])->first();

    //                     if ($report) {
    //                         $report->update([
    //                             'total_quantity' => DB::raw('total_quantity + ' . $item['total_grams'])
    //                         ]);
    //                     } else {

    //                         BranchRawMaterialsReport::created([
    //                             'branch_id'          => $validated['to_id'],
    //                             'ingredients_id'     => $item['raw_material_id'],
    //                             'total_quantity'     => $item['total_grams']
    //                         ]);
    //                     }
    //                 } elseif ($validated['to_designation'] === 'Warehouse') {
    //                     $stock = WarehouseRmStocks::where([
    //                         'warehouse_id'       => $validated['to_id'],
    //                         'raw_material_id'    => $item['raw_material_id'],
    //                         'price_per_gram'     => $item['price_per_gram']
    //                     ])->first();

    //                     if ($stock) {
    //                         $stock->update([
    //                             'quantity'       => DB::raw('quantity + ' . $item['quantity']),
    //                             'total_grams'    => DB::raw('total_grams + ' . $item['total_grams']),
    //                             'delivery_su_id' => $item['id']
    //                         ]);
    //                     } else {
    //                         WarehouseRmStocks::create([
    //                             'warehouse_id'       => $validated['to_id'],
    //                             'raw_material_id'    => $item['raw_material_id'],
    //                             'price_per_gram'     => $item['price_per_gram'],
    //                             'quantity'           => $item['quantity'],
    //                             'gram'               => $item['gram'],
    //                             'kilo'               => $item['kilo'],
    //                             'pcs'                => $item['pcs'],
    //                             'total_grams'        => $item['total_grams'],
    //                             'delivery_su_id'     => $item['id']
    //                         ]);
    //                     }

    //                     // Update warehouse report
    //                     $report = WarehouseRawMaterialsReport::where([
    //                         'warehouse_id'           => $validated['to_id'],
    //                         'raw_material_id'        => $item['raw_material_id'],
    //                     ])->first();

    //                     if ($report) {
    //                         $report->update([
    //                             'total_quantity'     => DB::raw('total_quantity + ' . $item['total_grams'])
    //                         ]);
    //                     } else {
    //                         WarehouseRawMaterialsReport::create([
    //                             'warehouse_id'       => $validated['to_id'],
    //                             'raw_material_id'    => $item['raw_material_id'],
    //                             'total_quantity'     => $item['total_grams']
    //                         ]);
    //                     }
    //                 }
    //             }
    //         }

    //         return response()->json([
    //             // 'message' => 'Delivery ' . $validated['status'] . ' successfully.',
    //             'message' => 'Delivery confirmed successfully.',
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message'    => 'Failed to process delivery.',
    //             'error'      => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function confirmDelivery(Request $request)
    // {
    //     try {
    //         // ✅ 1. Validate request
    //         $validated = $request->validate([
    //             'id'                 => 'required|integer|exists:raw_materials_deliveries,id',
    //             'from_id'            => 'nullable|integer',
    //             'from_designation'   => 'required|string|in:Branch,Warehouse,Supplier',
    //             'to_id'              => 'required|integer',
    //             'to_designation'     => 'required|string|in:Branch,Warehouse',
    //             'status'             => 'required|string|in:confirmed',
    //             'items'              => 'required|array',
    //             'items.*.id'                 => 'required|integer|exists:delivery_stocks_units,id',
    //             'items.*.raw_material_id'    => 'required|integer|exists:raw_materials,id',
    //             'items.*.quantity'           => 'required|numeric|min:1',
    //             'items.*.gram'               => 'nullable|numeric|min:0',
    //             'items.*.kilo'               => 'nullable|numeric|min:0',
    //             'items.*.pcs'                => 'nullable|numeric|min:0',
    //             'items.*.price_per_unit'     => 'required|numeric|min:0',
    //             'items.*.price_per_gram'     => 'required|numeric|min:0',
    //             'items.*.total_grams'        => 'required|numeric|min:0'
    //         ]);

    //         // ✅ 2. Update delivery status
    //         $delivery = RawMaterialsDelivery::findOrFail($validated['id']);
    //         $delivery->update(['status' => $validated['status']]);

    //         // ✅ 3. Process stocks only if confirmed
    //         if ($validated['status'] === 'confirmed') {
    //             foreach ($validated['items'] as $item) {

    //                 /**
    //                  * 🟢 Deduct FROM source
    //                  */
    //                 if ($validated['from_designation'] === 'Warehouse') {
    //                     $report = WarehouseRawMaterialsReport::where([
    //                         'warehouse_id'       => $validated['from_id'],
    //                         'raw_material_id'    => $item['raw_material_id'],
    //                     ])->first();

    //                     if ($report) {
    //                         $report->decrement('total_quantity', $item['total_grams']);
    //                     }
    //                 } elseif ($validated['from_designation'] === 'Branch') {
    //                     $report = BranchRawMaterialsReport::where([
    //                         'branch_id'          => $validated['from_id'],
    //                         'ingredients_id'     => $item['raw_material_id']
    //                     ])->first();

    //                     if ($report) {
    //                         $report->decrement('total_quantity', $item['total_grams']);
    //                     }
    //                 }

    //                 /**
    //                  * 🟢 Add TO destination
    //                  */
    //                 if ($validated['to_designation'] === 'Branch') {
    //                     // 🔍 Look for same raw_material_id AND same price_per_gram
    //                     $stock = BranchRmStocks::where([
    //                         'branch_id'          => $validated['to_id'],
    //                         'raw_material_id'    => $item['raw_material_id'],
    //                         'price_per_gram'     => $item['price_per_gram'],
    //                     ])->first();

    //                     if ($stock) {
    //                         // ✅ Same price_per_gram → Add to existing stock
    //                         $stock->update([
    //                             'quantity'        => DB::raw('quantity + ' . $item['total_grams']),
    //                             'delivery_su_id'  => $item['id'],
    //                         ]);
    //                     } else {
    //                         // ❌ Different price_per_gram → Create new stock entry
    //                         BranchRmStocks::create([
    //                             'branch_id'          => $validated['to_id'],
    //                             'raw_material_id'    => $item['raw_material_id'],
    //                             'price_per_gram'     => $item['price_per_gram'],
    //                             'quantity'           => $item['total_grams'],
    //                             'delivery_su_id'     => $item['id'],
    //                         ]);
    //                     }

    //                     // 🔄 Update branch report
    //                     $report = BranchRawMaterialsReport::where([
    //                         'branch_id'          => $validated['to_id'],
    //                         'ingredients_id'     => $item['raw_material_id']
    //                     ])->first();

    //                     if ($report) {
    //                         $report->increment('total_quantity', $item['total_grams']);
    //                     } else {
    //                         BranchRawMaterialsReport::create([
    //                             'branch_id'          => $validated['to_id'],
    //                             'ingredients_id'     => $item['raw_material_id'],
    //                             'total_quantity'     => $item['total_grams']
    //                         ]);
    //                     }

    //                 } elseif ($validated['to_designation'] === 'Warehouse') {
    //                     // 🔍 Look for same raw_material_id AND same price_per_gram
    //                     $stock = WarehouseRmStocks::where([
    //                         'warehouse_id'       => $validated['to_id'],
    //                         'raw_material_id'    => $item['raw_material_id'],
    //                         'price_per_gram'     => $item['price_per_gram']
    //                     ])->first();

    //                     if ($stock) {
    //                         // ✅ Same price_per_gram → Add to existing stock
    //                         $stock->update([
    //                             'quantity'           => DB::raw('quantity + ' . $item['quantity']),
    //                             'total_grams'        => DB::raw('total_grams + ' . $item['total_grams']),
    //                             'delivery_su_id'     => $item['id']
    //                         ]);
    //                     } else {
    //                         // ❌ Different price_per_gram → Create new record
    //                         WarehouseRmStocks::create([
    //                             'warehouse_id'       => $validated['to_id'],
    //                             'raw_material_id'    => $item['raw_material_id'],
    //                             'price_per_gram'     => $item['price_per_gram'],
    //                             'quantity'           => $item['quantity'],
    //                             'gram'               => $item['gram'],
    //                             'kilo'               => $item['kilo'],
    //                             'pcs'                => $item['pcs'],
    //                             'total_grams'        => $item['total_grams'],
    //                             'delivery_su_id'     => $item['id']
    //                         ]);
    //                     }

    //                     // 🔄 Update warehouse report
    //                     $report = WarehouseRawMaterialsReport::where([
    //                         'warehouse_id'       => $validated['to_id'],
    //                         'raw_material_id'    => $item['raw_material_id']
    //                     ])->first();

    //                     if ($report) {
    //                         $report->increment('total_quantity', $item['total_grams']);
    //                     } else {
    //                         WarehouseRawMaterialsReport::create([
    //                             'warehouse_id'       => $validated['to_id'],
    //                             'raw_material_id'    => $item['raw_material_id'],
    //                             'total_quantity'     => $item['total_grams']
    //                         ]);
    //                     }
    //                 }
    //             }
    //         }

    //         return response()->json([
    //             'message' => 'Delivery confirmed successfully.'
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message'    => 'Failed to process delivery.',
    //             'error'      => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function confirmDelivery(Request $request)
    {
        try{
            // ✅ 1. Validate request
            $validated = $request->validate([
                'id'                         => 'required|integer|exists:raw_materials_deliveries,id',
                'employee_id'                => 'required|integer|exists:employees,id',
                'from_id'                    => 'nullable|integer',
                'from_designation'           => 'required|string|in:Branch,Warehouse,Supplier',
                'to_id'                      => 'required|integer',
                'to_designation'             => 'required|string|in:Branch,Warehouse',
                'status'                     => 'required|string|in:confirmed',
                'items'                      => 'required|array',
                'items.*.id'                 => 'required|integer|exists:delivery_stocks_units,id',
                'items.*.raw_material_id'    => 'required|integer|exists:raw_materials,id',
                'items.*.quantity'           => 'required|numeric|min:1',
                'items.*.gram'               => 'nullable|numeric|min:0',
                'items.*.kilo'               => 'nullable|numeric|min:0',
                'items.*.pcs'                => 'nullable|numeric|min:0',
                'items.*.price_per_unit'     => 'required|numeric|min:0',
                'items.*.price_per_gram'     => 'required|numeric|min:0',
                'items.*.total_grams'        => 'required|numeric|min:0'
            ]);

            DB::beginTransaction();

            // ✅ 2. Update delivery status
            $delivery = RawMaterialsDelivery::findOrFail($validated['id']);
            $delivery->update([
                'status' => $validated['status'],
                'approved_by' => $validated['employee_id']
            ]);

            // ✅ 3. Also update supplier record if this delivery came from a supplier
            $supplierRecord = SupplierRecord::where('rm_delivery_id', $delivery->id)->first();
            if ($supplierRecord) {
                $supplierRecord->update(['status' => $validated['status']]);
            }

            // ✅ 4. Process stocks only if confirmed
            if ($validated['status'] === 'confirmed') {
                foreach ($validated['items'] as $item) {
                    /**
                     * 🟢 Deduct FROM source
                     */
                    if ($validated['from_designation'] === 'Warehouse') {
                        $report = WarehouseRawMaterialsReport::where([
                            'warehouse_id' => $validated['from_id'],
                            'raw_material_id' => $item['raw_material_id']
                        ])->first();

                        if ($report) {
                            $report->decrement('total_quantity', $item['total_grams']);
                        }
                    } elseif ($validated['from_designation'] === 'Branch') {
                        $report = BranchRawMaterialsReport::where([
                            'branch_id' => $validated['from_id'],
                            'ingredients_id' => $item['raw_material_id']
                        ])->first();

                        if ($report) {
                            $report->decrement('total_quantity', $item['total_grams']);
                        }
                    }

                    /**
                     * 🟢 Add TO destination
                     */
                    if ($validated['to_designation'] === 'Branch') {
                        $stock = BranchRmStocks::where([
                            'branch_id'              => $validated['to_id'],
                            'raw_material_id'        => $item['raw_material_id'],
                            'price_per_gram'         => $item['price_per_gram']
                        ])->first();

                        if ($stock) {
                            $stock->update([
                                'quantity'           => DB::raw('quantity + ' . $item['total_grams']),
                                'delivery_su_id'     => $item['id'],
                            ]);
                        } else {
                            BranchRmStocks::create([
                                'branch_id'          => $validated['to_id'],
                                'raw_material_id'    => $item['raw_material_id'],
                                'price_per_gram'     => $item['price_per_gram'],
                                'quantity'           => $item['total_grams'],
                                'delivery_su_id'     => $item['id']
                            ]);
                        }

                        $report = BranchRawMaterialsReport::where([
                            'branch_id' => $validated['to_id'],
                            'ingredients_id' => $item['raw_material_id']
                        ])->first();

                        if ($report) {
                            $report->increment('total_quantity', $item['total_grams']);
                        } else {
                            BranchRawMaterialsReport::create([
                                'branch_id'          => $validated['to_id'],
                                'ingredients_id'     => $item['raw_material_id'],
                                'total_quantity'     => $item['total_grams']
                            ]);
                        }
                    } elseif ($validated['to_designation'] === 'Warehouse') {
                        $stock = WarehouseRmStocks::where([
                            'warehouse_id' => $validated['to_id'],
                            'raw_material_id' => $item['raw_material_id'],
                            'price_per_gram' => $item['price_per_gram']
                        ])->first();

                        if ($stock) {
                            $stock->update([
                                'quantity' => DB::raw('quantity +' . $item['quantity']),
                                'total_grams' => DB::raw('total_grams + ' . $item['total_grams']),
                                'delivery_su_id' => $item['id']
                            ]);
                        } else {
                            WarehouseRmStocks::create([
                                'warehouse_id' => $validated['to_id'],
                                'raw_material_id' => $item['raw_material_id'],
                                'price_per_gram' => $item['price_per_gram'],
                                'quantity' => $item['quantity'],
                                'gram' => $item['gram'],
                                'kilo' => $item['kilo'],
                                'pcs' => $item['pcs'],
                                'total_grams' => $item['total_grams'],
                                'delivery_su_id' => $item['id']
                            ]);
                        }

                        $report = WarehouseRawMaterialsReport::where([
                            'warehouse_id' => $validated['to_id'],
                            'raw_material_id' => $item['raw_material_id']
                        ])->first();

                        if ($report) {
                            $report->increment('total_quantity', $item['total_grams']);
                        } else {
                            WarehouseRawMaterialsReport::create([
                                'warehouse_id' => $validated['to_id'],
                                'raw_material_id' => $item['raw_material_id'],
                                'total_quantity' => $item['total_grams']
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Delivery confirmed successfully.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to process delivery.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function declineDelivery(Request $request)
    {
        try {
            // ✅ 1. Validate request
            $validated = $request->validate([
                'id'         => 'required|integer|exists:raw_materials_deliveries,id',
                'employee_id' => 'required|integer|exists:employees,id',
                'remarks'    => 'required|string|max:1000'
            ]);

            // ✅ 2. Find the delivery
            $delivery = RawMaterialsDelivery::findOrFail($validated['id']);

            // ✅ 3. Update status + remarks
            $delivery->status = 'declined';
            $delivery->remarks = $validated['remarks'];
            $delivery->approved_by = $validated['employee_id'];
            $delivery->save();

            // ✅ 4. Return success
            return response()->json([
                'message'    => 'Delivery declined successfully.',
                'status'     => 'success'
            ], 200);

        } catch (\Exception $e) {
            // ❌ Handle errors

            return response()->json([
                'message'    => 'Failed to decline delivery.',
                'error'      => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Show the form for creating a new resource.
     */
    // public function create(Request $request)
    // {
    //     // 1️⃣ Validate the request before doing anything
    //     $validator = Validator::make(request()->all(), [
    //         'from_id'                => 'nullable|integer',
    //         'from_designation'       => 'nullable|string',
    //         'from_name'              => 'nullable|string',
    //         'to_id'                  => 'nullable|integer',
    //         'to_designation'         => 'nullable|string',
    //         'remarks'                => 'nullable|string',
    //         'status'                 => 'nullable|string',
    //         'raw_materials_groups'   => 'required|array',

    //         // Validate each item in the raw_materials_groups array
    //         'raw_materials_groups.*.raw_materials_id'    => 'required|exists:raw_materials,id',
    //         'raw_materials_groups.*.raw_materials_name'  => 'required|string',
    //         'raw_materials_groups.*.unit_type'           => 'required|string',
    //         'raw_materials_groups.*.quantity'            => 'required|numeric',
    //         'raw_materials_groups.*.price_per_unit'      => 'required|numeric',
    //         'raw_materials_groups.*.price_per_gram'      => 'required|numeric',
    //         'raw_materials_groups.*.pcs'                 => 'required|integer',
    //         'raw_materials_groups.*.kilo'                => 'nullable|numeric',
    //         'raw_materials_groups.*.gram'                => 'required|numeric',
    //         'raw_materials_groups.*.category'            => 'required|string',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'message'    => 'Validation Error',
    //             'errors'     => $validator->errors()
    //         ], 422);
    //     }

    //     // 2️⃣ Wrap in transaction
    //     try {

    //         $delivery = DB::transaction(function () use ($request) {
    //             // ✅ Step 1: Create Raw Materials Delivery
    //             $rawMaterialsDelivery = RawMaterialsDelivery::create([
    //                 'from_id'            => $request->input('from_id'),
    //                 'from_designation'   => $request->input('from_designation'),
    //                 'from_name'          => $request->input('from_name'),
    //                 'to_id'              => $request->input('to_id'),
    //                 'to_designation'     => $request->input('to_designation'),
    //                 'remarks'            => $request->input('remarks'),
    //                 'status'             => $request->input('status', 'Pending')
    //             ]);

    //             // ✅ Step 2: Create supplier_record entry
    //             $supplierRecord = SupplierRecord::create([
    //                 'rm_delivery_id' => $rawMaterialsDelivery->id,
    //                 'supplier_name' => $request->input('from_name'),
    //                 'status' => $request->input('status', 'pending'),
    //             ]);

    //             // ✅ Step 3: Create child records (DeliveryStocksUnit + SupplierIngredients)
    //             foreach ($request->input('raw_materials_groups') as $group) {

    //                 // Create delivery stock entry
    //                 DeliveryStocksUnit::create([
    //                     'rm_delivery_id'     => $rawMaterialsDelivery->id,
    //                     'raw_material_id'    => $group['raw_materials_id'],
    //                     'unit_type'          => $group['unit_type'],
    //                     'category'           => $group['category'],
    //                     'quantity'           => $group['quantity'],
    //                     'price_per_unit'     => $group['price_per_unit'],
    //                     'price_per_gram'     => $group['price_per_gram'],
    //                     'gram'               => $group['gram'],
    //                     'pcs'                => $group['pcs'],
    //                     'kilo'               => $group['kilo']
    //                 ]);

    //                 // Create supplier ingredients entry
    //                 SupplierIngredient::create([
    //                     'supplier_record_id' => $supplierRecord->id,
    //                     'raw_material_id' => $group['raw_materials_id'],
    //                     'quantity' => $group['quantity'],
    //                     'price_per_gram' => $group['price_per_gram'],
    //                     'price_per_unit' => $group['price_per_unit'],
    //                     'pcs' => $group['pcs'],
    //                     'kilo' => $group['kilo'],
    //                     'gram' => $group['gram'],
    //                     'category' => $group['category']
    //                 ]);

    //             }

    //             return $rawMaterialsDelivery;
    //         });

    //         return response()->json([
    //             'message'        => 'Raw materials delivery created successfully',
    //             'data'           =>  $delivery->load('items')
    //         ], 201);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message'        => ' Failed to create raw materials delivery',
    //             'error'          => $e->getMessage()
    //             ], 500);
    //     }

    // }

    public function create(Request $request)
    {
        // 1️⃣ Validate the request before doing anything
        $validator = Validator::make($request->all(), [
            'employee_id'            => 'required|integer',
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
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $delivery = DB::transaction(function () use ($request) {
                // ✅ Step 1: Create Raw Materials Delivery
                $rawMaterialsDelivery = RawMaterialsDelivery::create([
                    'employee_id'        => $request->input('employee_id'),
                    'from_id'            => $request->input('from_id'),
                    'from_designation'   => $request->input('from_designation'),
                    'from_name'          => $request->input('from_name'),
                    'to_id'              => $request->input('to_id'),
                    'to_designation'     => $request->input('to_designation'),
                    'remarks'            => $request->input('remarks'),
                    'status'             => $request->input('status', 'Pending')
                ]);

                // ✅ Step 2: Conditionally create Supplier Record
                $supplierRecord = null;
                if (strtolower($request->input('from_designation')) === 'supplier') {
                    $supplierRecord = SupplierRecord::create([
                        'rm_delivery_id'  => $rawMaterialsDelivery->id,
                        'supplier_name'  => $request->input('from_name'),
                        'status'          => $request->input('status', 'Pending'),
                    ]);
                }

                // ✅ Step 3: Create Delivery Stock Units
                foreach ($request->input('raw_materials_groups') as $group) {
                    DeliveryStocksUnit::create([
                        'rm_delivery_id'     => $rawMaterialsDelivery->id,
                        'raw_material_id'    => $group['raw_materials_id'],
                        'unit_type'          => $group['unit_type'],
                        'category'           => $group['category'],
                        'quantity'           => $group['quantity'],
                        'price_per_unit'     => $group['price_per_unit'],
                        'price_per_gram'     => $group['price_per_gram'],
                        'gram'               => $group['gram'],
                        'pcs'                => $group['pcs'],
                        'kilo'               => $group['kilo']
                    ]);

                    // ✅ Step 4: If from_designation = Supplier → also save supplier_ingredients
                    if ($supplierRecord) {
                        SupplierIngredient::create([
                            'supplier_record_id' => $supplierRecord->id,
                            'raw_material_id'    => $group['raw_materials_id'],
                            'quantity'           => $group['quantity'],
                            'price_per_gram'     => $group['price_per_gram'],
                            'price_per_unit'     => $group['price_per_unit'],
                            'pcs'                => $group['pcs'],
                            'kilo'               => $group['kilo'],
                            'gram'               => $group['gram'],
                            'category'           => $group['category'],
                        ]);
                    }
                }

                return $rawMaterialsDelivery;
            });

            return response()->json([
                'message' => 'Raw materials delivery created successfully',
                'data'    => $delivery->load('items')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create raw materials delivery',
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

    public function editDeliveryStocks(Request $request)
    {
        try {
            $validated = $request->validate([
                'to_designation'     => 'required|string|in:Warehouse,Branch',
                'item_id'            => 'required|integer|exists:delivery_stocks_units,id',
                'raw_material_id'    => 'required|integer|exists:raw_materials,id',
                'quantity'           => 'nullable|numeric|min:1',
                'gram'               => 'nullable|numeric|min:0',
                'kilo'               => 'nullable|numeric|min:0',
                'pcs'                => 'nullable|numeric|min:0',
                'price_per_unit'     => 'nullable|numeric|min:0',
                'price_per_gram'     => 'nullable|numeric',
                'category'           => 'nullable|string',
                'unit_type'          => 'nullable|string'
            ]);

            DB::beginTransaction();

            // ✅ 2. Find the existing item
            $item = DeliveryStocksUnit::findOrFail($validated['item_id']);

            // ✅ Get old and new quantity
            $oldQuantity = $item->quantity ?? 0;
            $newQuantity = $validated['quantity'] ?? $item->quantity ?? 0;

            // ✅ Get old and new grams
            $oldTotalGrams = $item->gram ?? 0;
            $newTotalGrams = $validated['gram'] ?? $item->gram ?? 0;

            // ✅ Compute difference
            $quantityDifference = $oldQuantity - $newQuantity;
            $gramsDifference = $oldTotalGrams - $newTotalGrams;

            // ✅ Update DeliveryStocksUnit

            if (!$item) {
                return response()->json([
                    'message' => "Item not found in delivery stocks."
                ], 404);
            }

            // ✅ 3. Update item fields
            $item->update([
                'quantity'           => $validated['quantity'] ?? $item->quantity,
                'gram'               => $newTotalGrams,
                'kilo'               => $validated['kilo'] ?? $item->kilo,
                'pcs'                => $validated['pcs'] ?? $item->pcs,
                'price_per_unit'     => $validated['price_per_unit'] ?? $item->price_per_unit,
                'price_per_gram'     => $validated['price_per_gram'] ?? $item->price_per_gram,
                'category'           => $validated['category'] ?? $item->category,
                'unit_type'          => $validated['unit_type'] ?? $item->unit_type,
            ]);

            // ✅ Adjust stock based on to_designation
            if ($validated['to_designation'] === 'Branch') {
                $stock = BranchRmStocks::where('raw_material_id', $validated['raw_material_id'])
                    ->where('delivery_su_id', $item->id )
                    ->first();

                if ($stock) {
                    // Update existing record
                    $stock->update([
                        'quantity'           => $stock->quantity - $gramsDifference,
                        'price_per_gram'     => $validated['price_per_gram']
                    ]);
                } else {
                    // Create if doesn't exist
                    BranchRmStocks::create([
                        'raw_material_id'    => $validated['raw_material_id'],
                        'quantity'           => max(0, -$gramsDifference), // Prevent negatives
                        'price_per_gram'     => $validated['price_per_gram'],
                        'delivery_su_id'     => $item->id,
                    ]);
                }
            } elseif ($validated['to_designation'] === 'Warehouse') {
                $stock = WarehouseRmStocks::where('raw_material_id', $validated['raw_material_id'])
                    ->where('delivery_su_id', $item->id)
                    ->first();

                if ($stock) {
                    $stock->update([
                        'quantity'       => $stock->quantity - $quantityDifference,
                        'total_grams'    => $stock->total_grams - $gramsDifference,
                        'price_per_gram' => $validated['price_per_gram'],
                    ]);
                } else {
                    // Create record if not found
                    WarehouseRmStocks::create([
                        'raw_material_id'    => $validated['raw_material_id'],
                        'quantity'           => max(0, -$gramsDifference),
                        'total_grams'        => max(0, -$gramsDifference),
                        'price_per_gram'     => $validated['price_per_gram'],
                        'delivery_su_id'     => $item->id
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message'    => 'Delivery stock item updated successfully.',
                'data'       => $item,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message'    => 'Error updating stock:' . $e->getMessage()
            ], 500);
        }
    }

    public function updateDeliveryDate($id, Request $request)
    {
        try {
            $request->validate([
                'created_at' => 'required|date',
            ]);

            // Fetch delivery record
            $delivery = RawMaterialsDelivery::findOrFail($id);

            // Combine date from forntend + current backend time
            $currentTime = now()->format('H:i:s');
            $newDateTime = Carbon::createFromFormat('Y-m-d H:i:s', "{$request->created_at} {$currentTime}");

            // Update main dalivery created_at
            $delivery->created_at = $newDateTime;
            $delivery->save();

            // Update all related DeliveryStocksUnit records
            DeliveryStocksUnit::where('rm_delivery_id', $id)
            ->update(['created_at' => $newDateTime]);

            // Update all related SupplierRecord records
            SupplierRecord::where('rm_delivery_id', $id)
            ->update(['created_at' => $newDateTime]);

            return response()->json([
                'message' => 'Delivery date updated successfully.',
                'new_created_at' => $newDateTime->toDateTimeString(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update delivery date.',
                'error' => $e->getMessage(),
            ], 500);
        }
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
