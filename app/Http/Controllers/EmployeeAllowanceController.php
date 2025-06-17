<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\EmployeeAllowance;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Redis;

class EmployeeAllowanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */

     public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 7);
        $search = $request->query('search', '');

        $query = EmployeeAllowance::with('employee')->orderBy('created_at', 'desc');

        if (!empty($search)) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('firstname', 'like', "%$search%")
                ->orWhere('lastname', 'like', "%$search%");
            });
        }

        if ($perPage == 0) {
            $data = $query->get();
            return response()->json([
                'data' => $data,
                'total' => $data->count(),
                'per_page' => $data->count(),
                'current_page' => 1,
                'last_page' => 1
            ]);
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($paginated);
    }

    // public function index(Request $request)
    // {
    //     $page = $request->get('page', 1);
    //     $perPage = $request->get('per_page', 7);
    //     $search = $request->query('search', '');

    //     $query = EmployeeAllowance::with('employee')->orderBy('created_at', 'desc');

    //     if (!empty($search)) {
    //         $query->whereHas('employee',function ($q) use ($search) {
    //             $q->where('firstname', 'like', "%$search%")
    //             ->orWhere('lastname', 'like', "%$search%");
    //         });
    //     }

    //     if ($perPage == 0) {
    //         $data = $query->get();
    //         return response()->json([
    //             'data' => $data,
    //             'total' =>$data->count(),
    //             'per_page' =>$data->count(),
    //             'current_page' => 1,
    //             'last_page' => 1
    //         ]);
    //     }

    //     $paginated = $query->paginate($perPage, ['*'], 'page', $page);

    //     return response()->json($paginated);

    // }

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

        $employeeAllowance = EmployeeAllowance::create($validateData)->load('employee');

        return response()->json([
            'data' => [$employeeAllowance],
            'total' => 1,
            'per_page' => 1,
            'current_page' => 1,
            'last_page' => 1,
        ], 201);
    }

    public function updateEmployeeAllowance(Request $request, $id)
    {
        $validateData = $request->validate([
            'amount' => 'required|numeric'
        ]);

        $employeeAllowance = EmployeeAllowance::find($id);

        if (!$employeeAllowance) {
            return response()->json(['error' => 'Employee allowance not found.'], 404);
        }

        $employeeAllowance->update([
            'amount' => $validateData['amount']
        ]);

        return response()->json($employeeAllowance, 200);
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
