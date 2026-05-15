<?php

namespace App\Http\Controllers;

use App\Models\EmployeeBenefit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use App\Services\HistoryLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            return response()->json([
                'error' => 'Benefits for this employee already exists.'
            ], 409);
        }

        DB::beginTransaction();
        try {
            $benefit = EmployeeBenefit::create($validateData)->load('employee');

            // LOG-26 — Benefit: Created
            HistoryLogService::log([
                'user_id'          => Auth::id(),
                'report_id'        => $benefit->id,
                'type_of_report'   => 'Benefit',
                'name'             => "Benefits set for: " . ($benefit->employee->firstname ?? 'Employee'),
                'action'           => 'created',
                'updated_data'     => $benefit->toArray(),
            ]);

            DB::commit();

            // Match the same format as index
            return response()->json([
                'data'           => [$benefit],
                'total'          => 1,
                'per_page'       => 1,
                'current_page'   => 1,
                'last_page'      => 1
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create benefits', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateEmployeeSssNumberBenefit(Request $request, $id)
    {
        $validateData = $request->validate([
            'sss_number' => 'required|string'
        ]);

        $benefit = EmployeeBenefit::findOrFail($id);
        $oldValue = $benefit->sss_number;
        $benefit->update($validateData);

        // LOG-26 — Benefit: Updated SSS Number
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'report_id'        => $benefit->id,
            'type_of_report'   => 'Benefit',
            'name'             => "SSS Number updated for: " . ($benefit->employee->firstname ?? 'Employee'),
            'action'           => 'updated',
            'updated_field'    => 'sss_number',
            'original_data'    => $oldValue,
            'updated_data'     => $benefit->sss_number,
        ]);

        return response()->json($benefit, 200);
    }

    public function updateEmployeeSssBenefit(Request $request, $id)
    {
        $validateData = $request->validate([
            'sss' => 'required|numeric'
        ]);

        $benefit = EmployeeBenefit::findOrFail($id);
        $oldValue = $benefit->sss;
        $benefit->update($validateData);

        // LOG-26 — Benefit: Updated SSS Amount
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'report_id'        => $benefit->id,
            'type_of_report'   => 'Benefit',
            'name'             => "SSS amount updated for: " . ($benefit->employee->firstname ?? 'Employee'),
            'action'           => 'updated',
            'updated_field'    => 'sss',
            'original_data'    => $oldValue,
            'updated_data'     => $benefit->sss,
        ]);

        return response()->json($benefit, 200);
    }

    public function updateEmployeeHdmfNumberBenefit(Request $request, $id)
    {

        $validateData = $request->validate([
            'hdmf_number' => 'required|string'
        ]);

        $benefit = EmployeeBenefit::findOrFail($id);
        $oldValue = $benefit->hdmf_number;
        $benefit->update($validateData);

        // LOG-26 — Benefit: Updated HDMF Number
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'report_id'        => $benefit->id,
            'type_of_report'   => 'Benefit',
            'name'             => "HDMF Number updated for: " . ($benefit->employee->firstname ?? 'Employee'),
            'action'           => 'updated',
            'updated_field'    => 'hdmf_number',
            'original_data'    => $oldValue,
            'updated_data'     => $benefit->hdmf_number,
        ]);

        return response()->json($benefit, 200);
    }
    public function updateEmployeeHdmfBenefit(Request $request, $id)
    {
        $validateData = $request->validate([
            'hdmf' => 'required|numeric'
        ]);

        $benefit = EmployeeBenefit::findOrFail($id);
        $oldValue = $benefit->hdmf;
        $benefit->update($validateData);

        // LOG-26 — Benefit: Updated HDMF Amount
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'report_id'        => $benefit->id,
            'type_of_report'   => 'Benefit',
            'name'             => "HDMF amount updated for: " . ($benefit->employee->firstname ?? 'Employee'),
            'action'           => 'updated',
            'updated_field'    => 'hdmf',
            'original_data'    => $oldValue,
            'updated_data'     => $benefit->hdmf,
        ]);

        return response()->json($benefit, 200);
    }

    public function updateEmployeePhicNumberBenefit(Request $request, $id)
    {
        $validateData = $request->validate([
            'phic_number' => 'required|string'
        ]);

        $benefit = EmployeeBenefit::findOrFail($id);
        $oldValue = $benefit->phic_number;
        $benefit->update($validateData);

        // LOG-26 — Benefit: Updated PHIC Number
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'report_id'        => $benefit->id,
            'type_of_report'   => 'Benefit',
            'name'             => "PHIC Number updated for: " . ($benefit->employee->firstname ?? 'Employee'),
            'action'           => 'updated',
            'updated_field'    => 'phic_number',
            'original_data'    => $oldValue,
            'updated_data'     => $benefit->phic_number,
        ]);

        return response()->json($benefit, 200);

    }

    public function updateEmployeePhicBenefit(Request $request, $id)
    {
        $validateData = $request->validate([
            'phic' => 'required|numeric'
        ]);

        $benefit = EmployeeBenefit::findOrFail($id);
        $oldValue = $benefit->phic;
        $benefit->update($validateData);

        // LOG-26 — Benefit: Updated PHIC Amount
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'report_id'        => $benefit->id,
            'type_of_report'   => 'Benefit',
            'name'             => "PHIC amount updated for: " . ($benefit->employee->firstname ?? 'Employee'),
            'action'           => 'updated',
            'updated_field'    => 'phic',
            'original_data'    => $oldValue,
            'updated_data'     => $benefit->phic,
        ]);

        return response()->json($benefit, 200);
    }
}
