<?php

namespace App\Http\Controllers;

use App\Models\Uniform;
use App\Models\UniformPants;
use App\Models\UniformTshirt;
use Illuminate\Http\Request;

class UniformController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page        = $request->get('page', 1);
        $perPage     = $request->get('per_page', 7);
        $search      = $request->query('search', '');

        $query = Uniform::orderBy('created_at', 'desc')->with(['employee','tShirt','pants']);

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

    public function fetchUniformForDeduction($employeeId)
    {
        $uniform = Uniform::where('employee_id', $employeeId)
                        ->with(['tShirt', 'pants'])
                        ->where('remaining_payments', '>', 0)
                        ->get();
        if (!$uniform) {
            return response()->json(['message' => 'Uniform record not found for the specified employee.'], 404);
        }

        return response()->json($uniform);
    }

    public function searchUniform(Request $request)
    {
        $keyword = $request->input('keyword');

        $uniforms = Uniform::with('employee', 'tShirt', 'pants')
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

     return response()->json($uniforms);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'employee_id'            => 'required|integer|exists:employees,id',
            'numberOfPayments'       => 'required|integer',
            'totalAmount'            => 'required|numeric',
            'paymentPerPayroll'      => 'required|numeric',
            'remaining_payments'     => 'required|numeric',
            'pantsPcs'               => 'nullable|integer',
            'pantsPrice'             => 'nullable|numeric',
            'pantsSize'              => 'nullable|string',
            'tShirtPcs'              => 'nullable|integer',
            'tShirtPrice'            => 'nullable|numeric',
            'tShirtsize'             => 'nullable|string'
        ]);

        $uniform = Uniform::create([
            'employee_id'            => $validatedData['employee_id'],
            'number_of_payments'     => $validatedData['numberOfPayments'],
            'total_amount'           => $validatedData['totalAmount'],
            'payments_per_payroll'   => $validatedData['paymentPerPayroll'],
            'remaining_payments'     => $validatedData['remaining_payments']
        ]);

        if ($validatedData['pantsPcs'] && $validatedData['pantsPrice'] && $validatedData['pantsSize']) {
             UniformPants::create([
                'uniform_id'     => $uniform->id,
                'size'           => $validatedData['pantsSize'],
                'pcs'            => $validatedData['pantsPcs'],
                'price'          => $validatedData['pantsPrice'],
            ]);
        }

        if ($validatedData['tShirtPcs'] && $validatedData['tShirtPrice'] && $validatedData['tShirtsize']) {
             UniformTshirt::create([
                'uniform_id'     => $uniform->id,
                'size'           => $validatedData['tShirtsize'],
                'pcs'            => $validatedData['tShirtPcs'],
                'price'          => $validatedData['tShirtPrice'],
            ]);
        }

        $uniform->load('employee');

        return response()->json([
           'data'            => [$uniform],
           'total'           => 1,
           'per_page'        => 1,
           'current_page'    => 1,
           'last_page'       => 1
        ], 201);
    }

    public function updateUniform(Request $request, $id)
    {
        $validatedData = $request->validate([
            'employee_id'            => 'required|integer|exists:employees,id',
            'numberOfPayments'       => 'required|integer',
            'totalAmount'            => 'required|numeric',
            'paymentPerPayroll'      => 'required|numeric',
            'remaining_payments'     => 'required|numeric',
            'pantsPcs'               => 'nullable|integer',
            'pantsPrice'             => 'nullable|numeric',
            'pantsSize'              => 'nullable|string',
            'tShirtPcs'              => 'nullable|integer',
            'tShirtPrice'            => 'nullable|numeric',
            'tShirtsize'             => 'nullable|string'
        ]);

        $uniform = Uniform::findOrFail($id);

        // Update the uniform details
        $uniform->update([
            'employee_id'            => $validatedData['employee_id'],
            'number_of_payments'     => $validatedData['numberOfPayments'],
            'total_amount'           => $validatedData['totalAmount'],
            'payments_per_payroll'   => $validatedData['paymentPerPayroll'],
            'remaining_payments'     => $validatedData['remaining_payments']
        ]);

        //Handle T-Shirt Update
        if ($validatedData['tShirtPcs'] && $validatedData['tShirtPrice'] && $validatedData['tShirtsize']) {
            $tShirt = $uniform->tShirt()->first();

            if ($tShirt) {
                $tShirt->update([
                    'size'   => $validatedData['tShirtsize'],
                    'pcs'    => $validatedData['tShirtPcs'],
                    'price'  => $validatedData['tShirtPrice']
                ]);
            } else {
                UniformTshirt::create([
                    'uniform_id'     => $uniform->id,
                    'size'           => $validatedData['tShirtsize'],
                    'pcs'            => $validatedData['tShirtPcs'],
                    'price'          => $validatedData['tShirtPrice']
                ]);
            }
        } else {
            // Delete t-shirts if previously existing but now removed
            $uniform->tShirt()->delete();
        }

        //Handle Pants Update
         if ($validatedData['pantsPcs'] && $validatedData['pantsPrice'] && $validatedData['pantsSize'])
         {
            $pants = $uniform->pants()->first();

            if ($pants) {
                $pants->update([
                    'size'   => $validatedData['pantsSize'],
                    'pcs'    => $validatedData['pantsPcs'],
                    'price'  => $validatedData['pantsPrice'],
                ]);
            } else {
                UniformPants::create([
                    'uniform_id'     => $uniform->id,
                    'size'           => $validatedData['pantsSize'],
                    'pcs'            => $validatedData['pantsPcs'],
                    'price'          => $validatedData['pantsPrice'],
                ]);
            }
        } else {
            // Delete pants if previously existing but now removed
            $uniform->pants()->delete();
        }
        //Handle T-Shirt Update
         return response()->json([
            'message' => 'Uniform updated successfully',
            'uniform' => $uniform->fresh(['pants', 'tShirt']),
         ]);
    }

}
