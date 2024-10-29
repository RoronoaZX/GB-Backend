<?php

namespace App\Http\Controllers;

use App\Models\EmploymentType;
use Illuminate\Http\Request;

class EmploymentTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employementType = EmploymentType::orderBy('category', 'asc')->get();

        return response()->json($employementType, 200);
    }
    // public function getEmploymentType()
    // {
    //     $employmentType = EmploymentType::orderBy('category', 'acs')->get();

    //     return response()->json($employmentType, 200);
    // }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(EmploymentType $employmentType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmploymentType $employmentType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmploymentType $employmentType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmploymentType $employmentType)
    {
        //
    }
}
