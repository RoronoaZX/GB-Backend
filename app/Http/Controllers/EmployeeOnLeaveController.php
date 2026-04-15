<?php

namespace App\Http\Controllers;

use App\Models\EmployeeOnLeave;
use Illuminate\Http\Request;

class EmployeeOnLeaveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'duration_value' => 'required|numeric|min:0',
            'duration_type' => 'required|string',
            'leave_type' => 'required|string',
            'handled_by' => 'required|integer|exists:employees,id',
            'status' => 'required|string',
            'reason' => 'nullable|string',
        ]);

        $employeeOnLeave = EmployeeOnLeave::create($validated);

        return response()->json($employeeOnLeave, 201);
    }
}
