<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\EmployeeAllowance;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Redis;
use App\Services\HistoryLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeAllowanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */

     public function index(Request $request)
    {
        $page        = $request->get('page', 1);
        $perPage     = $request->get('per_page', 7);
        $search      = $request->query('search', '');

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
                'data'           => $data,
                'total'          => $data->count(),
                'per_page'       => $data->count(),
                'current_page'   => 1,
                'last_page'      => 1
            ]);
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($paginated);
    }

    /**
     * Search a resource in storage.
     */

     public function searchAllowance(Request $request)
     {
         $keyword    = $request->input('keyword');

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
            'employee_id'    => 'required|exists:employees,id',
            'amount'         => 'required|numeric'
        ]);

        $existingAllowance = EmployeeAllowance::where('employee_id', $validateData['employee_id'])->first();

        if($existingAllowance) {
            return response()->json(['error' => 'Allowance for this employee already exists.'], 409);
        }

        DB::beginTransaction();
        try {
            $employeeAllowance = EmployeeAllowance::create($validateData)->load('employee');

            // LOG-26 — Allowance: Created
            HistoryLogService::log([
                'user_id'          => Auth::id(),
                'report_id'        => $employeeAllowance->id,
                'type_of_report'   => 'Allowance',
                'name'             => "Allowance created for: " . ($employeeAllowance->employee->firstname ?? 'Employee'),
                'action'           => 'created',
                'updated_data'     => $employeeAllowance->toArray(),
            ]);

            DB::commit();

            return response()->json([
                'data'           => [$employeeAllowance],
                'total'          => 1,
                'per_page'       => 1,
                'current_page'   => 1,
                'last_page'      => 1,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create allowance', 'error' => $e->getMessage()], 500);
        }
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

        DB::beginTransaction();
        try {
            $oldAmount = $employeeAllowance->amount;
            $employeeAllowance->update([
                'amount' => $validateData['amount']
            ]);

            // LOG-26 — Allowance: Updated
            HistoryLogService::log([
                'user_id'          => Auth::id(),
                'report_id'        => $employeeAllowance->id,
                'type_of_report'   => 'Allowance',
                'name'             => "Allowance updated for: " . ($employeeAllowance->employee->firstname ?? 'Employee'),
                'action'           => 'updated',
                'updated_field'    => 'amount',
                'original_data'    => $oldAmount,
                'updated_data'     => $employeeAllowance->amount,
            ]);

            DB::commit();

            return response()->json($employeeAllowance, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update allowance', 'error' => $e->getMessage()], 500);
        }
    }
}
