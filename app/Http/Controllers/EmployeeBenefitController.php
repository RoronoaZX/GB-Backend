<?php

namespace App\Http\Controllers;

use App\Models\EmployeeBenefit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class EmployeeBenefitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page        = $request->get('page', 1);
        $perPage     = $request->get('per_page', 7);
        $search      = $request->query('search', '');

        $query       = EmployeeBenefit::with('employee')->orderBy('created_at', 'desc');

        if (!empty($search)) {
            $query->whereHas('employee',function ($q) use ($search) {
                $q->where('firstname', 'like', "%$search%")
                ->orWhere('lastname', 'like', "%$search%");
            }
        );
        }

        if ($perPage == 0) {
            $data = $query->get();
            return response()->json([
            'data'           => $data,
            'total'          => $data->count(),
            'per_page'       => $data->count(),
            'current_page'   => 1,
            'last_page'      => 1

            ]);
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($paginated, 200);
    }

    /**
     * Display a listing of the resource.
     */
    public function fetchEmployeeBenefitsForDeduction($employee_id)
    {
       $employeeBenefits = EmployeeBenefit::with('employee')
                            ->where('employee_id', $employee_id)
                            ->first();

        return response()->json($employeeBenefits);
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
            'employee_id'    => 'required|exists:employees,id',
            'sss_number'     => 'required|string',
            'sss'            => 'required|numeric',
            'hdmf_number'    => 'required|string',
            'hdmf'           => 'required|numeric',
            'phic_number'    => 'required|string',
            'phic'           => 'required|numeric'
        ]);

        $existingBenefits = EmployeeBenefit::where('employee_id', $validateData['employee_id'])->first();

        if ($existingBenefits) {
            return response()->json(['error' => 'Benefits for this employee already exists.'], 409);
        }

        $benefit = EmployeeBenefit::create($validateData)->load('employee');

        // Match the same format as index
        return response()->json([
            'data'           => [$benefit],
            'total'          => 1,
            'per_page'       => 1,
            'current_page'   => 1,
            'last_page'      => 1
        ], 201);
    }

    public function updateEmployeeSssNumberBenefit(Request $request, $id)
    {
        $validateData = $request->validate([
            'sss_number' => 'required|string'
        ]);

        $benefit = EmployeeBenefit::findOrFail($id);
        $benefit->update($validateData);

        return response()->json($benefit, 200);
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

    public function updateEmployeeHdmfNumberBenefit(Request $request, $id)
    {

        $validateData = $request->validate([
            'hdmf_number' => 'required|string'
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

    public function updateEmployeePhicNumberBenefit(Request $request, $id)
    {
        $validateData = $request->validate([
            'phic_number' => 'required|string'
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
