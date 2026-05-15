<?php

namespace App\Http\Controllers;

use App\Models\EmploymentType;
use Illuminate\Http\Request;
use App\Services\HistoryLogService;
use Illuminate\Support\Facades\Auth;

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

        $oldSalary = $employmentTypeSalary->salary;
        $employmentTypeSalary->update([
            'salary' => $validateData['salary']
        ]);

        // LOG-34 — Employment Type: Updated Salary
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'report_id'        => $id,
            'type_of_report'   => 'System Config',
            'name'             => "Salary updated for: " . $employmentTypeSalary->category,
            'action'           => 'updated',
            'updated_field'    => 'salary',
            'original_data'    => $oldSalary,
            'updated_data'     => $employmentTypeSalary->salary,
        ]);

        return response()->json($employmentTypeSalary, 200);
    }

}
