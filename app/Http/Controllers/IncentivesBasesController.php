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
            'number_of_employees' => 'required|numeric',
            'target' => 'required|numeric',
            'baker' => 'required|numeric',
            'lamesador' => 'required|numeric',
            'hornero' => 'required|numeric',
        ]);

        $incentivesBases = [
            'number_of_employees' => $validateData['number_of_employees'],
            'target' => $validateData['target'],
            'baker_multiplier' => $validateData['baker'],
            'lamesador_multiplier' => $validateData['lamesador'],
            'hornero_incentives' => $validateData['hornero'],
        ];

        IncentivesBases::create($incentivesBases);

        return response()->json($incentivesBases, 201);

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
