<?php

namespace App\Http\Controllers;

use App\Models\BreadOut;
use App\Models\RepurposeLog;
use App\Models\SpoilageLog;
use App\Models\Product;
use App\Models\RawMaterial;
use App\Models\BranchProduct;
use App\Models\WarehouseRmStocks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepurposeController extends Controller
{
    public function processConversion(Request $request)
    {
        $request->validate([
            'bread_out_id'           => 'required|exists:bread_outs,id',
            'action_type'            => 'required|in:toasted,crumbs,transfer',
            'output_quantity'        => 'required|numeric|min:0.01',
            'output_id'              => 'required_unless:action_type,transfer|integer',
            'destination_branch_id'  => 'required_if:action_type,transfer|exists:branches,id',
        ]);

        try {
            DB::beginTransaction();

            $breadOut = BreadOut::findOrFail($request->bread_out_id);

            if ($breadOut->status !== 'received') {
                return response()->json([
                    'message' => 'Bread Out must be received by supervisor before converting.'
                ], 400);
            }

            $logData = [
                'bread_out_id'       => $breadOut->id,
                'action_type'        => $request->action_type,
                'output_quantity'    => $request->output_quantity,
            ];

            if ($request->action_type === 'toasted') {
                $product = Product::findOrFail($request->output_id);
                $logData['outputable_id'] = $product->id;
                $logData['outputable_type'] = Product::class;

                $branchProduct = BranchProduct::firstOrCreate(
                    [
                        'branches_id'    => $breadOut->branch_id,
                        'product_id'     => $product->id
                    ],
                    [
                        'total_quantity' => 0
                    ]
                );
                $branchProduct->total_quantity += $request->output_quantity;
                $branchProduct->save();

                $breadOut->status = 'repurposed';
            }
            elseif ($request->action_type === 'crumbs') {
                $rawMaterial = RawMaterial::findOrFail($request->output_id);
                $logData['outputable_id'] = $rawMaterial->id;
                $logData['outputable_type'] = RawMaterial::class;

                $warehouseStock = WarehouseRmStocks::firstOrCreate(
                    ['raw_material_id'   => $rawMaterial->id],
                    ['total_quantity'    => 0]
                );
                $warehouseStock->total_quantity += $request->output_quantity;
                $warehouseStock->save();

                $breadOut->status = 'repurposed';
            }
            elseif ($request->action_type === 'transfer') {
                $logData['destination_branch_id'] = $request->destination_branch_id;

                $branchProduct = BranchProduct::firstOrCreate(
                    [
                        'branches_id'    => $request->destination_branch_id,
                        'product_id'     => $breadOut->product_id
                    ],
                    [
                        'total_quantity' => 0
                    ]
                );
                $branchProduct->total_quantity += $request->output_quantity;
                $branchProduct->save();

                $breadOut->status = 'transferred';
            }

            RepurposeLog::create($logData);
            $breadOut->save();

            DB::commit();

            return response()->json([
                'message'    => 'Conversion successful',
                'breadOut'   => $breadOut
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message'    => 'Error processing conversion',
                'error'      => $e->getMessage()
            ], 500);
        }
    }

    public function processSpoilage(Request $request)
    {
        $request->validate([
            'bread_out_id'    => 'required|exists:bread_outs,id',
            'quantity'        => 'required|integer|min:1',
            'reason'          => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();
            $breadOut = BreadOut::findOrFail($request->bread_out_id);

            SpoilageLog::create([
                'bread_out_id'    => $breadOut->id,
                'quantity'        => $request->quantity,
                'reason'          => $request->reason,
            ]);

            $breadOut->status  = 'spoiled';
            $breadOut->save();

            DB::commit();

            return response()->json([
                'message' => 'Spoilage logged successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message'    => 'Error logging spoilage',
                'error'      => $e->getMessage()
            ], 500);
        }
    }
}
