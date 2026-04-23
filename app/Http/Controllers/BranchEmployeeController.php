<?php

namespace App\Http\Controllers;

use App\Models\BranchEmployee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchEmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    public function searchUserWithBranch(Request $request)
    {
        $keyword  = $request->input('keyword');
        $branchId = $request->input('branch_id');

        $users = User::join('branch_employees', 'users.id', '=', 'branch_employees.user_id')
                    ->where('branch_employees.branch_id', $branchId)
                    ->where('users.name', 'like', '%' . $keyword . '%')
                    ->select('users.*', 'branch_employees.branch_id')
                    ->get();

        return response()->json($users);
    }

    public function searchBranchEmployee(Request $request)
    {
        $branchId        = $request->input('branch_id');
        $searchKeyword   = $request->input('keyword');

        $employees       = BranchEmployee::byBranch($branchId)
                            ->whereHas('employee', function ($query) use ($searchKeyword) {
                                $query->where(function ($q) use ($searchKeyword) {
                                    $q->where('firstname', 'LIKE', '%' . $searchKeyword . '%')
                                    ->orWhere('lastname', 'LIKE', '%' . $searchKeyword . '%');
                                });
                            })
                            ->with('employee')
                            ->take(7)
                            ->get();

            return response()->json($employees);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id'   => 'required|exists:employees,id',
            'branch_id'     => 'required|exists:branches,id',
            'time_in'       => 'required|string|max:10',
            'time_out'      => 'required|string|max:10',
        ]);

        $branchEmployee = BranchEmployee::create([
            'branch_id'     => $request->branch_id,
            'employee_id'   => $request->employee_id,
            'time_in'       => $request->time_in,
            'time_out'      => $request->time_out,
         ]);

         return response()->json([
            'message'           => 'Branch employee designation created successfully.',
            'branchEmployee'    => $branchEmployee
         ], 201);
    }
}
