<?php

namespace App\Http\Controllers;

use App\Models\BranchEmployee;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = Employee::orderBy('created_at', 'asc')
            ->take(7)
            ->get();

        return response()->json($employees);
    }

    public function fetchAllEmployee()
    {
        $employee = Employee::orderBy('created_at', 'desc')->get();
        return response()->json($employee, 201);
    }

    public function fetchEmployeeWithEmploymentType()
    {
        $employees = Employee::with('employmentType')->orderBy('created_at', 'desc')->take(7)->get();
        return response()->json($employees, 201);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function searchEmployees(Request $request)
    {
        $keyword = $request->input('keyword');

        // Search by firstname or lastname
        $employees = Employee::where('firstname', 'like', "%$keyword%")
            ->orWhere('lastname', 'like', "%$keyword%")
            ->take(7)
            ->get();

        // Check if employees are found
        if ($employees->isEmpty()) {
            return response()->json([], 200); // Return an empty array if no results
        }

        return response()->json($employees, 200);
    }

    public function searchEmployeesWithDesignation(Request $request)
    {
        $keyword = $request->input('keyword');

        // Search by firstname or lastname
        $employees = Employee::with('branchEmployee.branch')
            ->where('firstname', 'like', "%$keyword%")
            ->orWhere('lastname', 'like', "%$keyword%")
            ->take(7)
            ->get();

        // Check if employees are found
        if ($employees->isEmpty()) {
            return response()->json([], 200); // Return an empty array if no results
        }

        return response()->json($employees, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validateEmployee = $request->validate([
            'employment_type_id' => 'required|integer',
            'firstname' => 'required|string|max:255',
            'middlename' => 'required|string|max:255',
            'lastname' =>  'required|string|max:255',
            'birthdate' => 'required|date',
            'phone' => 'required|string|max:25',
            'address' => 'required|string|max:255',
            'sex' => 'required|string|in:Male,Female',
            'position' =>  'required|string|max:255',
        ]);

        // Create the employee
        $employee = Employee::create($validateEmployee);

        // Retrieve the employee with the same structure as the index method
        $employee = Employee::with('employmentType')
            ->where('id', $employee->id)
            ->first();

        return response()->json([
            'message' => 'Employee successfully created',
            'employee' => $employee,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        //
    }
}
