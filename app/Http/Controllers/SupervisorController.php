<?php

namespace App\Http\Controllers;
use App\Models\Branch;

use Illuminate\Http\Request;

class SupervisorController extends Controller
{
    public function fetchSupervisorUnderBranch($employee_id)
    {
        $branches = Branch::where('employee_id',$employee_id)->with('employees', 'warehouse')->get();

        if ($branches->isEmpty()){
            return response()->json(['message' => 'No branches found for this employee'], 404);
        }

        return response()->json($branches);
    }

}
