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
use Illuminate\Support\Facades\Auth;
use App\Services\HistoryLogService;

use Symfony\Contracts\Service\Attribute\Required;

class RawMaterialsDeliveryController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        try {
            // Get pagination parameters from the request, default to 5 items per page
            $perPage     = $request->input('per_page', 5);
            $search      = $request->input('search');

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
            $perPage         = $request->input('per_page', 5);
            $search          = $request->input('search');
            $toDesignation   = $request->query('to_designation');

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
                'message'    => 'Failed to fetch deliveries',
                'error'      => $e->getMessage()
            ], 500);
        }
    }

    public function fetchPendingDelivery($id, Request $request)
    {
        try {
            // Status defaults to "pending" if not privided
            $status          = $request->query('status', 'pending');
            $toDesignation   = $request->query('to_designation');

            $query           = RawMaterialsDelivery::with(['items.rawMaterial', 'employee']);

            // Filter by status + destination id + designation
            $query->where('status', $status)
                  ->where('to_id', $id);

            if ($toDesignation) {
                $query->where('to_designation', $toDesignation);
            }

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
                'message'    => 'Pending deliveries fetched successfully',
                'data'       => $deliveries->map(function ($delivery) {
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
            $perPage         = $request->input('per_page', 3);
            $search          = $request->input('search');
            $status          = $request->query('status', 'confirmed');
            $toDesignation   = $request->query('to_designation');

            $query           = RawMaterialsDelivery::with(['items.rawMaterial', 'employee', 'approvedBy']);

            // Filter by status + destination id + designation
            $query->where('status', $status)
                  ->where('to_id', $id);

            if ($toDesignation) {
                $query->where('to_designation', $toDesignation);
            }

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

    public function fetchDeclinedDelivery($id, Request $request)
    {
        try {
            // Status defaults to "declined" if not provided
            $perPage         = $request->input('per_page', 3);
            $search          = $request->input('search');
            $status          = $request->query('status', 'declined');
            $toDesignation   = $request->query('to_designation');

            $query           = RawMaterialsDelivery::with(['items.rawMaterial', 'employee', 'approvedBy']);

            // Filter by status + destination id + designation
            $query->where('status', $status)
                  ->where('to_id', $id);

            if ($toDesignation) {
                $query->where('to_designation', $toDesignation);
            }

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

    public function confirmDelivery(Request $request)
    {
        try {
            // ✅ 1. Validate request
            $validated = $request->validate([
                'id'                         => 'required|integer|exists:raw_materials_deliveries,id',
                'employee_id'                => 'required|integer', // Relaxed exists for debugging
                'from_id'                    => 'nullable|integer',
                'from_designation'           => 'required|string|in:Branch,Warehouse,Supplier',
                'to_id'                      => 'required|integer',
                'to_designation'             => 'required|string|in:Branch,Warehouse',
                'status'                     => 'required|string|in:confirmed',
                'items'                      => 'required|array',
                'items.*.id'                 => 'required|integer|exists:delivery_stocks_units,id',
                'items.*.raw_material_id'    => 'required|integer|exists:raw_materials,id',
                'items.*.quantity'           => 'required|numeric|min:0',
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
                'status'         => $validated['status'],
                'approved_by'    => $validated['employee_id']
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
                            'warehouse_id'       => $validated['from_id'],
                            'raw_material_id'    => $item['raw_material_id']
                        ])->first();

                        if ($report) {
                            $report->decrement('total_quantity', (float)($item['total_grams'] ?? 0));
                        }

                        $stock = WarehouseRmStocks::where([
                            'warehouse_id'       => $validated['from_id'],
                            'raw_material_id'    => $item['raw_material_id'],
                            'price_per_gram'     => $item['price_per_gram']
                        ])->first();

                        if ($stock) {
                            $stock->decrement('quantity', (float)($item['quantity'] ?? 0));
                            $stock->decrement('total_grams', (float)($item['total_grams'] ?? 0));
                            if ($stock->total_grams <= 0) {
                                $stock->delete();
                            }
                        }
                    } elseif ($validated['from_designation'] === 'Branch') {
                        $report = BranchRawMaterialsReport::where([
                            'branch_id'          => $validated['from_id'],
                            'ingredients_id'     => $item['raw_material_id']
                        ])->first();

                        if ($report) {
                            $report->decrement('total_quantity', (float)($item['total_grams'] ?? 0));
                        }

                        $stock = BranchRmStocks::where([
                            'branch_id'          => $validated['from_id'],
                            'raw_material_id'    => $item['raw_material_id'],
                            'price_per_gram'     => $item['price_per_gram']
                        ])->first();

                        if ($stock) {
                            $stock->decrement('quantity', (float)($item['total_grams'] ?? 0));
                            if ($stock->quantity <= 0) {
                                $stock->delete();
                            }
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
                                'quantity'           => DB::raw('quantity + ' . (float)($item['total_grams'] ?? 0)),
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
                            'branch_id'          => $validated['to_id'],
                            'ingredients_id'     => $item['raw_material_id']
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
                            'warehouse_id'       => $validated['to_id'],
                            'raw_material_id'    => $item['raw_material_id'],
                            'price_per_gram'     => $item['price_per_gram']
                        ])->first();

                        if ($stock) {
                            $stock->update([
                                'quantity'           => DB::raw('quantity + ' . (float)($item['quantity'] ?? 0)),
                                'total_grams'        => DB::raw('total_grams + ' . (float)($item['total_grams'] ?? 0)),
                                'delivery_su_id'     => $item['id']
                            ]);
                        } else {
                            WarehouseRmStocks::create([
                                'warehouse_id'       => $validated['to_id'],
                                'raw_material_id'    => $item['raw_material_id'],
                                'price_per_gram'     => $item['price_per_gram'],
                                'quantity'           => $item['quantity'],
                                'gram'               => $item['gram'],
                                'kilo'               => $item['kilo'],
                                'pcs'                => $item['pcs'],
                                'total_grams'        => $item['total_grams'],
                                'delivery_su_id'     => $item['id']
                            ]);
                        }

                        $report = WarehouseRawMaterialsReport::where([
                            'warehouse_id'       => $validated['to_id'],
                            'raw_material_id'    => $item['raw_material_id']
                        ])->first();

                        if ($report) {
                            $report->increment('total_quantity', $item['total_grams']);
                        } else {
                            WarehouseRawMaterialsReport::create([
                                'warehouse_id'       => $validated['to_id'],
                                'raw_material_id'    => $item['raw_material_id'],
                                'total_quantity'     => $item['total_grams']
                            ]);
                        }
                    }
                }
            }

            // LOG-07 — Raw Materials Delivery: Branch Receive
            HistoryLogService::log([
                'user_id'          => Auth::id(),
                'report_id'        => $delivery->id,
                'type_of_report'   => 'Raw Materials Delivery',
                'name'             => "Received delivery from " . ($delivery->from_name ?? 'Unknown'),
                'action'           => 'received',
                'updated_data'     => [
                    'status' => 'confirmed',
                    'items_count' => count($validated['items'])
                ],
                'designation'      => $delivery->to_id,
                'designation_type' => strtolower($delivery->to_designation),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Delivery confirmed successfully.'
            ], 200);

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return response()->json([
                'message'    => 'Failed to confirm delivery: ' . $e->getMessage(),
                'line'       => $e->getLine(),
                'trace'      => $e->getTraceAsString()
            ], 500);
        }
    }


    public function declineDelivery(Request $request)
    {
        try {
            // ✅ 1. Validate request
            $validated = $request->validate([
                'id'             => 'required|integer|exists:raw_materials_deliveries,id',
                'employee_id'    => 'required|integer|exists:employees,id',
                'remarks'        => 'required|string|max:1000'
            ]);

            // ✅ 2. Find the delivery
            $delivery = RawMaterialsDelivery::findOrFail($validated['id']);

            // ✅ 3. Update status + remarks
            $delivery->status        = 'declined';
            $delivery->remarks       = $validated['remarks'];
            $delivery->approved_by   = $validated['employee_id'];
            $delivery->save();

            // LOG — Raw Materials Delivery: Declined
            HistoryLogService::log([
                'user_id'          => Auth::id(),
                'report_id'        => $delivery->id,
                'type_of_report'   => 'Raw Materials Delivery',
                'name'             => "Declined delivery from " . ($delivery->from_name ?? 'Unknown'),
                'action'           => 'declined',
                'updated_data'     => [
                    'remarks' => $delivery->remarks
                ],
                'designation'      => $delivery->to_id,
                'designation_type' => strtolower($delivery->to_designation),
            ]);

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

    public function store(Request $request)
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
            'raw_materials_groups.*.pcs'                 => 'required|numeric',
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
                    'employee_id'        => (int) $request->input('employee_id'),
                    'from_id'            => (int) ($request->input('from_id') ?? 0),
                    'from_designation'   => $request->input('from_designation'),
                    'from_name'          => $request->input('from_name'),
                    'to_id'              => (int) ($request->input('to_id') ?? 0),
                    'to_designation'     => $request->input('to_designation'),
                    'remarks'            => $request->input('remarks'),
                    'status'             => $request->input('status', 'pending')
                ]);

                // ✅ Step 2: Conditionally create Supplier Record
                $supplierRecord = null;
                if (strtolower($request->input('from_designation') ?? '') === 'supplier') {
                    $supplierRecord = SupplierRecord::create([
                        'rm_delivery_id'     => $rawMaterialsDelivery->id,
                        'supplier_name'      => $request->input('from_name'),
                        'status'             => $request->input('status', 'Pending'),
                    ]);
                }

                // ✅ Step 3: Create Delivery Stock Units
                foreach ($request->input('raw_materials_groups') as $group) {
                    $qty = round((float)($group['quantity'] ?? 0), 3);
                    $pricePerUnit = round((float)($group['price_per_unit'] ?? 0), 3);
                    $pricePerGram = round((float)($group['price_per_gram'] ?? 0), 3);
                    $gram = round((float)($group['gram'] ?? 0), 3);
                    $pcs = round((float)($group['pcs'] ?? 0), 3);
                    $kilo = round((float)($group['kilo'] ?? 0), 3);
                    $category = $group['category'];

                    $totalRequestedGrams = 0;
                    if (in_array($category, ['sack', 'can', 'bottle', 'tub', 'gallon', 'kilo', 'gram'])) {
                        if ($category === 'gram') {
                            $totalRequestedGrams = $qty;
                        } else {
                            $totalRequestedGrams = $qty * $gram;
                        }
                    } else if ($category === 'box' || $category === 'pcs') {
                        $totalRequestedGrams = $qty * $gram; 
                    }

                    if (strtolower($request->input('from_designation')) === 'warehouse' && $totalRequestedGrams > 0) {
                        $fromId = (int) ($request->input('from_id') ?? 0);
                        $stocks = \App\Models\WarehouseRmStocks::where('warehouse_id', $fromId)
                            ->where('raw_material_id', $group['raw_materials_id'])
                            ->where('total_grams', '>', 0)
                            ->orderBy('created_at', 'asc')
                            ->get();
                            
                        $remainingGramsToFulfill = $totalRequestedGrams;
                        $splits = [];
                        
                        foreach ($stocks as $stock) {
                            if ($remainingGramsToFulfill <= 0) break;
                            
                            $takeGrams = min($stock->total_grams, $remainingGramsToFulfill);
                            $remainingGramsToFulfill -= $takeGrams;
                            $proportion = $takeGrams / $totalRequestedGrams;
                            
                            $ratio = ($pricePerGram > 0) ? ($pricePerUnit / $pricePerGram) : 0;
                            $newPricePerUnit = $ratio > 0 ? $stock->price_per_gram * $ratio : $pricePerUnit;

                            $splits[] = [
                                'qty' => $qty * $proportion,
                                'pricePerUnit' => $newPricePerUnit,
                                'pricePerGram' => $stock->price_per_gram,
                                'gram' => $gram,
                                'pcs' => $pcs * $proportion,
                                'kilo' => $kilo * $proportion
                            ];
                        }
                        
                        if ($remainingGramsToFulfill > 0) {
                             $proportion = $remainingGramsToFulfill / $totalRequestedGrams;
                             $splits[] = [
                                'qty' => $qty * $proportion,
                                'pricePerUnit' => $pricePerUnit,
                                'pricePerGram' => $pricePerGram,
                                'gram' => $gram,
                                'pcs' => $pcs * $proportion,
                                'kilo' => $kilo * $proportion
                             ];
                        }
                        
                        foreach ($splits as $split) {
                            DeliveryStocksUnit::create([
                                'rm_delivery_id'     => $rawMaterialsDelivery->id,
                                'raw_material_id'    => $group['raw_materials_id'],
                                'unit_type'          => $group['unit_type'],
                                'category'           => $group['category'],
                                'quantity'           => round($split['qty'], 3),
                                'price_per_unit'     => round($split['pricePerUnit'], 3),
                                'price_per_gram'     => round($split['pricePerGram'], 4),
                                'gram'               => round($split['gram'], 3),
                                'pcs'                => round($split['pcs'], 3),
                                'kilo'               => round($split['kilo'], 3)
                            ]);
                        }
                        
                    } else if (strtolower($request->input('from_designation')) === 'branch' && $totalRequestedGrams > 0) {
                        $fromId = (int) ($request->input('from_id') ?? 0);
                        $stocks = \App\Models\BranchRmStocks::where('branch_id', $fromId)
                            ->where('raw_material_id', $group['raw_materials_id'])
                            ->where('quantity', '>', 0)
                            ->orderBy('created_at', 'asc')
                            ->get();
                            
                        $remainingGramsToFulfill = $totalRequestedGrams;
                        $splits = [];
                        
                        foreach ($stocks as $stock) {
                            if ($remainingGramsToFulfill <= 0) break;
                            $takeGrams = min($stock->quantity, $remainingGramsToFulfill);
                            $remainingGramsToFulfill -= $takeGrams;
                            $proportion = $takeGrams / $totalRequestedGrams;
                            
                            $ratio = ($pricePerGram > 0) ? ($pricePerUnit / $pricePerGram) : 0;
                            $newPricePerUnit = $ratio > 0 ? $stock->price_per_gram * $ratio : $pricePerUnit;

                            $splits[] = [
                                'qty' => $qty * $proportion,
                                'pricePerUnit' => $newPricePerUnit,
                                'pricePerGram' => $stock->price_per_gram,
                                'gram' => $gram,
                                'pcs' => $pcs * $proportion,
                                'kilo' => $kilo * $proportion
                            ];
                        }
                        
                        if ($remainingGramsToFulfill > 0) {
                             $proportion = $remainingGramsToFulfill / $totalRequestedGrams;
                             $splits[] = [
                                'qty' => $qty * $proportion,
                                'pricePerUnit' => $pricePerUnit,
                                'pricePerGram' => $pricePerGram,
                                'gram' => $gram,
                                'pcs' => $pcs * $proportion,
                                'kilo' => $kilo * $proportion
                             ];
                        }
                        
                        foreach ($splits as $split) {
                            DeliveryStocksUnit::create([
                                'rm_delivery_id'     => $rawMaterialsDelivery->id,
                                'raw_material_id'    => $group['raw_materials_id'],
                                'unit_type'          => $group['unit_type'],
                                'category'           => $group['category'],
                                'quantity'           => round($split['qty'], 3),
                                'price_per_unit'     => round($split['pricePerUnit'], 3),
                                'price_per_gram'     => round($split['pricePerGram'], 4),
                                'gram'               => round($split['gram'], 3),
                                'pcs'                => round($split['pcs'], 3),
                                'kilo'               => round($split['kilo'], 3)
                            ]);
                        }
                    } else {
                        DeliveryStocksUnit::create([
                            'rm_delivery_id'     => $rawMaterialsDelivery->id,
                            'raw_material_id'    => $group['raw_materials_id'],
                            'unit_type'          => $group['unit_type'],
                            'category'           => $group['category'],
                            'quantity'           => $qty,
                            'price_per_unit'     => $pricePerUnit,
                            'price_per_gram'     => $pricePerGram,
                            'gram'               => $gram,
                            'pcs'                => $pcs,
                            'kilo'               => $kilo
                        ]);

                        // ✅ Step 4: If from_designation = Supplier → also save supplier_ingredients
                        if ($supplierRecord) {
                            SupplierIngredient::create([
                                'supplier_record_id' => $supplierRecord->id,
                                'raw_material_id'    => $group['raw_materials_id'],
                                'quantity'           => $qty,
                                'price_per_gram'     => $pricePerGram,
                                'price_per_unit'     => $pricePerUnit,
                                'pcs'                => $pcs,
                                'kilo'               => $kilo,
                                'gram'               => $gram,
                                'category'           => $group['category'],
                            ]);
                        }
                    }
                }

                // LOG-06 — Raw Materials Delivery: Create
                HistoryLogService::log([
                    'user_id'          => Auth::id(),
                    'report_id'        => $rawMaterialsDelivery->id,
                    'type_of_report'   => 'Raw Materials Delivery',
                    'name'             => "New delivery from " . ($rawMaterialsDelivery->from_name ?? 'Source') . " to " . $rawMaterialsDelivery->to_designation,
                    'action'           => 'created',
                    'updated_data'     => [
                        'from' => $rawMaterialsDelivery->from_name,
                        'to' => $rawMaterialsDelivery->to_designation,
                        'remarks' => $rawMaterialsDelivery->remarks,
                        'items' => $request->input('raw_materials_groups')
                    ],
                    'designation'      => $rawMaterialsDelivery->to_id,
                    'designation_type' => strtolower($rawMaterialsDelivery->to_designation),
                ]);

                return $rawMaterialsDelivery;
            });

            return response()->json([
                'message' => 'Raw materials delivery created successfully',
                'data'    => $delivery->load('items')
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Stocks Delivery Creation Error: ' . $e->getMessage(), [
                'stack' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return response()->json([
                'message' => 'Failed to create raw materials delivery',
                'error'   => $e->getMessage()
            ], 500);
        }
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
            $quantityDifference  = $oldQuantity - $newQuantity;
            $gramsDifference     = $oldTotalGrams - $newTotalGrams;

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
                'created_at' => 'required|string',
            ]);

            // 🕒 Parse frontend datetime (e.g. "2025-10-28 02:30 PM") in Manila timezone
            $parsedDateTime = Carbon::createFromFormat('Y-m-d h:i A', $request->created_at, 'Asia/Manila');

            // 🔄 Convert to UTC for database consistency
            $parsedDateTimeUTC = $parsedDateTime->copy()->setTimezone('UTC');

            // 🗃️ Fetch the delivery record
            $delivery = RawMaterialsDelivery::findOrFail($id);

            // ✏️ Update main delivery record
            $delivery->created_at = $parsedDateTimeUTC;
            $delivery->save();

            // ✏️ Update related DeliveryStocksUnit and SupplierRecord records
            DeliveryStocksUnit::where('rm_delivery_id', $id)
            ->update(['created_at' => $parsedDateTimeUTC]);

            // Update all related SupplierRecord records
            SupplierRecord::where('rm_delivery_id', $id)
            ->update(['created_at' => $parsedDateTimeUTC]);

            return response()->json([
                'message'            => 'Delivery date updated successfully.',
                'new_created_at'     => $parsedDateTime->toDateTimeString(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message'    => 'Failed to update delivery date.',
                'error'      => $e->getMessage(),
            ], 500);
        }
    }
}
