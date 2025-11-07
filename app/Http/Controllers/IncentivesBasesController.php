<?php

namespace App\Http\Controllers;

use App\Models\IncentivesBases;
use Illuminate\Http\Request;

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

        IncentivesBases::create($incentivesBases);

        return response()->json($incentivesBases, 201);

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

        $incentivesBases->update([
            'number_of_employees' => $validateData['number_of_employees']
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

        $incentiveBase->update([
            'target' => $request->target
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

        $incentiveBases->update([
            'baker_multiplier' => $request->baker_multiplier
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

        $incentiveBases->update([
            'lamesador_multiplier' => $request->lamesador_multiplier
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

        $incentiveBases->update([
            'hornero_incentives' => $request->hornero_incentives
        ]);

        return response()->json([
            'message'    => 'Hornero incentives updated successfully. ',
            'data'       => $incentiveBases
        ], 200);
    }
    /**
     * Display the specified resource.
     */
    public function show(IncentivesBases $incentivesBases)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(IncentivesBases $incentivesBases)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, IncentivesBases $incentivesBases)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IncentivesBases $incentivesBases)
    {
        //
    }
}
