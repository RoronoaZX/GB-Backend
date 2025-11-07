<?php

namespace App\Http\Controllers;

use App\Models\CashAdvance;
use Illuminate\Auth\Events\Validated;
use Illuminate\Http\Request;

class CashAdvanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page        = $request->get('page', 1);
        $perPage     = $request->get('per_page', 7);
        $search      = $request->query('search', '');

        $query       = CashAdvance::orderBy('created_at', 'desc')
                        ->with('employee');

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

        return response()->json($paginated, 200);
    }

    public function fetchCashAdvanceForDeduction($employee_id)
    {
        $cashAdvances = CashAdvance::where(['employee_id' => $employee_id])
                            ->where(function ($query) {
                                $query->where('remaining_payments', '>', 0.00);
                            })
                            ->get();

        return response()->json($cashAdvances);
    }

    /**
     * Store a resource in storage.
     */

    public function searchCashAdvance(Request $request)
    {
        $keyword = $request->input('keyword');

        $cashAdvances = CashAdvance::with('employee')
                            ->when($keyword !== null, function ($query) use ($keyword) {
                                $query->whereHas('employee', function($q) use ($keyword) {
                                    $q->where('firstname', 'LIKE', '%' . $keyword . '%')
                                    ->orWhere('middlename', 'LIKE', '%' . $keyword . '%')
                                    ->orWhere('lastname', 'LIKE', '%' . $keyword . '%');
                                });
                            }, function ($query) {
                                $query->orderBy('created_at', 'desc');
                            })
                            ->take(7)
                            ->get();

        return response()->json($cashAdvances);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'employee_id'            => 'required|exists:employees,id',
            'amount'                 => 'required|numeric',
            'number_of_payments'     => 'required|integer',
            'payment_per_payroll'    => 'required|numeric',
            'remaining_payments'     => 'required|numeric',
            'reason'                 => 'required|string'
        ]);

        $cashAdvance = CashAdvance::create($validatedData)
                            ->load('employee');

        return response()->json([
            'data'           => [$cashAdvance],
            'total'          => 1,
            'per_page'       => 1,
            'curren_page'    => 1,
            'last_page'      => 1
        ], 201);
    }

    public function updateCashAdvanceAmount(Request $request, $id)
    {
        $validatedData = $request->validate([
            'amount'     => 'required|numeric'
        ]);

        $cashAdvance = CashAdvance::find($id);

        if (!$cashAdvance) {
            return response()->json([
                'error' => 'Employee cash advance not found.'
            ], 404);
        }

        $cashAdvance->update([
            'amount'     => $validatedData['amount']
        ]);
        return response()->json($cashAdvance, 200);
    }

    public function updateCashAdvanceReason(Request $request, $id)
    {
        $validatedData = $request->validate([
            'reason'     => 'required|string|max:255'
        ]);

        $cashAdvance = CashAdvance::find($id);

        if (!$cashAdvance) {
            return response()->json([
                'error' => 'Employee cash advance not found.'
            ], 404);
        }

        $cashAdvance->update([
            'reason'     => $validatedData['reason']
        ]);
        return response()->json($cashAdvance, 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(CashAdvance $cashAdvance)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CashAdvance $cashAdvance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CashAdvance $cashAdvance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CashAdvance $cashAdvance)
    {
        //
    }
}
