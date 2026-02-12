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

    public function updateEmployeementTypeSalary(Request $request, $id)
    {
        $validateData = $request->validate([
            'salary' => 'required|numeric'
        ]);

        $employmentTypeSalary = EmploymentType::find($id);

        if (!$employmentTypeSalary) {
            return response()->json(['error' => 'Employment type not found.'], 404);
        }

        $employmentTypeSalary->update([
            'salary' => $validateData['salary']
        ]);

        return response()->json($employmentTypeSalary, 200);
    }

}
