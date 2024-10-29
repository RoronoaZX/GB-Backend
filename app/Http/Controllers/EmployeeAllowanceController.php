<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\EmployeeAllowance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class EmployeeAllowanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employeeAllowance = EmployeeAllowance::orderBy('created_at', 'desc')->with('employee')->take(7)->get();

        return response()->json($employeeAllowance, 200);
    }

    /**
     * Search a resource in storage.
     */

     public function searchAllowance(Request $request)
     {
         $keyword = $request->input('keyword');

         $allowances = EmployeeAllowance::with('employee')
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

         return response()->json($allowances);
     }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric'
        ]);

        $existingAllowance = EmployeeAllowance::where('employee_id', $validateData['employee_id'])->first();

        if($existingAllowance) {
            return response()->json(['error' => 'Allowance for this employee already exists.'], 409);
        }

        $employeeAllowance = EmployeeAllowance::create($validateData);
        return response()->json($employeeAllowance, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(EmployeeAllowance $employeeAllowance)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeeAllowance $employeeAllowance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeAllowance $employeeAllowance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeAllowance $employeeAllowance)
    {
        //
    }
}
