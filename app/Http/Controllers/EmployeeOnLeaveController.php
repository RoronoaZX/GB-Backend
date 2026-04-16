<?php

namespace App\Http\Controllers;

use App\Models\EmployeeOnLeave;
use Illuminate\Http\Request;

class EmployeeOnLeaveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = EmployeeOnLeave::with(['employee', 'handler']);

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('start_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        return response()->json($query->latest()->get());
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

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $leave = EmployeeOnLeave::find($id);

        if (!$leave) {
            return response()->json(['message' => 'Leave not found'], 404);
        }

        return response()->json($leave);
    }

    /**
     * Update the specified resource in storage
     */

    public function update(Request $request, $id) 
    {
        $leave = EmployeeOnLeave::find($id);

        if (!$leave) {
            return response()->json(['message' => 'Leave not found'], 404);
        }

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

        $leave->update($validated);

        return response()->json($leave);
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy($id) 
    {
        $leave = EmployeeOnLeave::find($id);

        if (!$leave) {
            return response()->json(['message' => 'Leave not found'], 404);
        }

        $leave->delete();

        return response()->json(['message' => 'Leave deleted successfully']);
    }

}
