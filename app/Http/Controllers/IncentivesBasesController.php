<?php

namespace App\Http\Controllers;

use App\Models\IncentivesBases;
use Illuminate\Http\Request;
use App\Services\HistoryLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IncentivesBasesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $incentivesBases = IncentivesBases::all();
        return response()->json($incentivesBases, 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'number_of_employees'    => 'required|numeric',
            'target'                 => 'required|numeric',
            'baker'                  => 'required|numeric',
            'lamesador'              => 'required|numeric',
            'hornero'                => 'required|numeric',
        ]);

        $incentivesBases = [
            'number_of_employees'    => $validateData['number_of_employees'],
            'target'                 => $validateData['target'],
            'baker_multiplier'       => $validateData['baker'],
            'lamesador_multiplier'   => $validateData['lamesador'],
            'hornero_incentives'     => $validateData['hornero'],
        ];

        DB::beginTransaction();
        try {
            $incentiveBase = IncentivesBases::create($incentivesBases);

            // LOG-26 — Incentive Base: Created
            HistoryLogService::log([
                'user_id'          => Auth::id(),
                'report_id'        => $incentiveBase->id,
                'type_of_report'   => 'Incentive',
                'name'             => "Incentive base created for " . $validateData['number_of_employees'] . " employees",
                'action'           => 'created',
                'updated_data'     => $incentiveBase->toArray(),
            ]);

            DB::commit();

            return response()->json($incentiveBase, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create incentive base', 'error' => $e->getMessage()], 500);
        }

    }

    public function updateNumberEmployee(Request $request, $id)
    {
        $validateData = $request->validate([
            'number_of_employees' => 'required|numeric',
        ]);

        $incentivesBases = IncentivesBases::find($id);

        if (!$incentivesBases) {
            return response()->json([
                'error' => 'Incentives bases not found.'
            ], 404);
        }

        $alreadyExists = IncentivesBases::where('number_of_employees', $validateData['number_of_employees'])
                            ->where('id', '!=', $id)
                            ->exists();

        if ($alreadyExists) {
            return response()->json([
                'error' => 'The number of employees already exists in another record.'
            ], 422);
        }

        $oldValue = $incentivesBases->number_of_employees;
        $incentivesBases->update([
            'number_of_employees' => $validateData['number_of_employees']
        ]);

        // LOG-26 — Incentive Base: Updated Number of Employees
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'report_id'        => $incentivesBases->id,
            'type_of_report'   => 'Incentive',
            'name'             => "Incentive base: Number of employees updated",
            'action'           => 'updated',
            'updated_field'    => 'number_of_employees',
            'original_data'    => $oldValue,
            'updated_data'     => $incentivesBases->number_of_employees,
        ]);

        return response()->json([
            'message'    => 'Number of employees updated successfully. ',
            'data'       => $incentivesBases
        ], 200);
    }

    public function updateTarget(Request $request, $id)
    {
        $request->validate([
            'target' => 'required|numeric'
        ]);

        $incentiveBase = IncentivesBases::find($id);

        if  (!$incentiveBase) {
            return response()->json([
                'error' => 'Incentives base not found.'
            ], 404);
        }

        $oldValue = $incentiveBase->target;
        $incentiveBase->update([
            'target' => $request->target
        ]);

        // LOG-26 — Incentive Base: Updated Target
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'report_id'        => $incentiveBase->id,
            'type_of_report'   => 'Incentive',
            'name'             => "Incentive base: Target updated",
            'action'           => 'updated',
            'updated_field'    => 'target',
            'original_data'    => $oldValue,
            'updated_data'     => $incentiveBase->target,
        ]);

        return response()->json([
            'message' => 'Target updated successfully.'
        ]);
    }
    public function updateBakerMultipier(Request $request, $id)
    {
        $request->validate([
            'baker_multiplier' => 'required|numeric',
        ]);

        $incentiveBases = IncentivesBases::find($id);

        if (!$incentiveBases) {
            return response()->json([
                'error' => 'Incentives bases not found.'
            ], 404);
        }

        $oldValue = $incentiveBases->baker_multiplier;
        $incentiveBases->update([
            'baker_multiplier' => $request->baker_multiplier
        ]);

        // LOG-26 — Incentive Base: Updated Baker Multiplier
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'report_id'        => $incentiveBases->id,
            'type_of_report'   => 'Incentive',
            'name'             => "Incentive base: Baker multiplier updated",
            'action'           => 'updated',
            'updated_field'    => 'baker_multiplier',
            'original_data'    => $oldValue,
            'updated_data'     => $incentiveBases->baker_multiplier,
        ]);

        return response()->json([
            'message'    => 'Target updated successfully.',
            'data'       => $incentiveBases
        ]);
    }

    public function updateLamesadorMultipier(Request $request, $id)
    {
        $request->validate([
            'lamesador_multiplier' => 'required|numeric',
        ]);

        $incentiveBases = IncentivesBases::find($id);

        if (!$incentiveBases) {
            return response()->json([
                'error' => 'Incentives bases not found.'
            ], 404);
        }

        $oldValue = $incentiveBases->lamesador_multiplier;
        $incentiveBases->update([
            'lamesador_multiplier' => $request->lamesador_multiplier
        ]);

        // LOG-26 — Incentive Base: Updated Lamesador Multiplier
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'report_id'        => $incentiveBases->id,
            'type_of_report'   => 'Incentive',
            'name'             => "Incentive base: Lamesador multiplier updated",
            'action'           => 'updated',
            'updated_field'    => 'lamesador_multiplier',
            'original_data'    => $oldValue,
            'updated_data'     => $incentiveBases->lamesador_multiplier,
        ]);

        return response()->json([
            'message'    => 'Lamesador multiplier updated successfully.',
            'data'       => $incentiveBases
        ]);
    }

    public function updateHorneroIncentives(Request $request, $id)
    {
        $request->validate([
            'hornero_incentives' => 'required|numeric',
        ]);

        $incentiveBases = IncentivesBases::find($id);

        if (!$incentiveBases) {
            return response()->json([
                'error' => 'Incentives bases not found.'
            ], 404);
        }

        $oldValue = $incentiveBases->hornero_incentives;
        $incentiveBases->update([
            'hornero_incentives' => $request->hornero_incentives
        ]);

        // LOG-26 — Incentive Base: Updated Hornero Incentives
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'report_id'        => $incentiveBases->id,
            'type_of_report'   => 'Incentive',
            'name'             => "Incentive base: Hornero incentives updated",
            'action'           => 'updated',
            'updated_field'    => 'hornero_incentives',
            'original_data'    => $oldValue,
            'updated_data'     => $incentiveBases->hornero_incentives,
        ]);

        return response()->json([
            'message'    => 'Hornero incentives updated successfully. ',
            'data'       => $incentiveBases
        ], 200);
    }
}
