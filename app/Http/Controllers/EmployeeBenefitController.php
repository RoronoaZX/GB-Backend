<?php

namespace App\Http\Controllers;

use App\Models\EmployeeBenefit;
use Illuminate\Http\Request;

class EmployeeBenefitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $benefit = EmployeeBenefit::orderBy('created_at', 'desc')->with('employee')->take(7)->get();

        return response()->json($benefit, 200);
    }

    /**
     * Search a resource in storage.
     */

    public function searchBenefit(Request $request)
    {
        $keyword = $request->input('keyword');

        $benefits = EmployeeBenefit::with('employee')
            ->when($keyword !== null, function ($query) use ($keyword) {
                $query->whereHas('employee', function($q) use ($keyword) {
                    $q->where('firstname', 'LIKE', '%' . $keyword . '%')
                      ->orWhere('middlename', 'LIKE', '%' . $keyword . '%')
                      ->orWhere('lastname', 'LIKE', '%' . $keyword . '%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->take(7)
            ->get();

            return response()->json($benefits);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'sss' => 'required|numeric',
            'hdmf' => 'required|numeric',
            'phic' => 'required|numeric'
        ]);

        $existingBenefits = EmployeeBenefit::where('employee_id', $validateData['employee_id'])->first();

        if($existingBenefits) {
            return response()->json(['error' => 'Benefits for this employee already exists.'], 409);
        }

        $benefit = EmployeeBenefit::create($validateData);

        return response()->json([
            $benefit, 201
        ]);

    }

    public function updateEmployeeSssBenefit(Request $request, $id)
    {
        $validateData = $request->validate([
            'sss' => 'required|numeric'
        ]);

        $benefit = EmployeeBenefit::findOrFail($id);
        $benefit->update($validateData);

        return response()->json($benefit, 200);
    }
    public function updateEmployeeHdmfBenefit(Request $request, $id)
    {
        $validateData = $request->validate([
            'hdmf' => 'required|numeric'
        ]);

        $benefit = EmployeeBenefit::findOrFail($id);
        $benefit->update($validateData);

        return response()->json($benefit, 200);
    }
    public function updateEmployeePhicBenefit(Request $request, $id)
    {
        $validateData = $request->validate([
            'phic' => 'required|numeric'
        ]);

        $benefit = EmployeeBenefit::findOrFail($id);
        $benefit->update($validateData);

        return response()->json($benefit, 200);
    }



    /**
     * Display the specified resource.
     */
    public function show(EmployeeBenefit $employeeBenefit)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeeBenefit $employeeBenefit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeBenefit $employeeBenefit)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeBenefit $employeeBenefit)
    {
        //
    }
}
