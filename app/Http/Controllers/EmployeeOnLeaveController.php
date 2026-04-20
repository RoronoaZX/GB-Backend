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
        $query = EmployeeOnLeave::with('employee');

        if ($request->has('branch_id')) {
            $query->whereHas('employee.branchEmployee', function($q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }

        if ($request->has('year')) {
            $query->whereYear('created_at', $request->year);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $leaves = $query->orderBy('created_at', 'desc')->get();

        return response()->json($leaves);
    }

    // Create new leave request
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id'        => 'required|exists:employees,id',
            'leave_type'         => 'required|string',
            'start_date'         => 'required|date',
            'end_date'           => 'required|date|after_or_equal:start_date',
            'reason'             => 'nullable|string',
            'status'             => 'sometimes|string|in:pending,approved,rejected,confirmed',
            'duration_type'      => 'required|string',
            'duration_value'     => 'required|integer',
            'handled_by'         => 'nullable|integer'
        ]);

        $leave = EmployeeOnLeave::create($validated);
        $leave->load('employee');

        return response()->json($leave, 201);
    }

    // Display specific leave request
    public function show($id)
    {
        $leave = EmployeeOnLeave::with('employee')->findOrFail($id);

        return response()->json($leave);
    }

    // Update leave request status (approve/reject)
    public function update(Request $request, $id)
    {
        $leave = EmployeeOnLeave::findOrFail($id);

        $validated = $request->validate([
            'status'     => 'required|in:pending,approved,rejected',
            'remarks'    => 'nullable|string'
        ]);

        $previousStatus = $leave->status;
        $newStatus = $request->status;

        $handledBy = $request->user() ? $request->user()->employee_id : null;

        $leave->update([
            'status'     => $newStatus,
            'remarks'    => $request->remarks ?? $leave->remarks,
            'start_date' => $request->start_date ?? $leave->start_date,
            'end_date'   => $request->end_date ?? $leave->end_date,
            'handled_by' => $handledBy ?? $leave->handled_by
        ]);

        $employee = $leave->employee;
        if ($employee && $leave->duration_type === 'days') {
            $duration = (int) $leave->duration_value;

            // Deduct upon approval
            if ($previousStatus !== 'approved' && $newStatus === 'approved') {
                if ($leave->leave_type === 'vacation_leave') {
                    $employee->decrement('vl_balance', $duration);
                } elseif ($leave->leave_type === 'sick_leave') {
                    $employee->decrement('sl_balance', $duration);
                } elseif ($leave->leave_type === 'emergency_leave') {
                    $employee->decrement('el_balance', $duration);
                }
            }

            // Refund balance if revoked
            if ($previousStatus === 'approved' && $newStatus !== 'approved') {
                if ($leave->leave_type === 'vacation_leave') {
                    $employee->increment('vl_balance', $duration);
                } elseif ($leave->leave_type === 'sick_leave') {
                    $employee->increment('sl_balance', $duration);
                } elseif ($leave->leave_type === 'emergency_leave') {
                    $employee->increment('el_balance', $duration);
                }
            }
        }

        return response()->json($leave);
    }

    // Delete leave request
    public function destroy($id)
    {
        $leave = EmployeeOnLeave::findOrFail($id);
        $leave->delete();

        return response()->json(['message' => 'Leave request deleted successfully']);
    }

    // Get leave request for current year
    public function getCurrentYearRequest(Request $request)
    {
        $branchId = $request->get('branch_id');

        // Removed the strict current-year constraint so ALL historical data is fetched
        $query = EmployeeOnLeave::query();

        if ($branchId) {
            $query->whereHas('employee.branchEmployee', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $leaves = $query->with('employee')->orderBy('created_at', 'desc')->get();

        return response()->json($leaves);
    }
}
