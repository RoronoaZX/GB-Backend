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
    public function index()
    {
        $uniform = Uniform::orderBy('created_at', 'desc')->with(['employee','tShirt','pants'])->take(7)->get();

        return response()->json($uniform, 200);
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
            'employee_id' => 'required|integer|exists:employees,id',
            'numberOfPayments' => 'required|integer',
            'totalAmount' => 'required|integer',
            'paymentPerPayroll' => 'required|integer',
            'pantsPcs' => 'nullable|integer',
            'pantsPrice' => 'nullable|integer',
            'pantsSize' => 'nullable|string',
            'tShirtPcs' => 'nullable|integer',
            'tShirtPrice' => 'nullable|integer',
            'tShirtsize' => 'nullable|string'
        ]);

        $uniform = Uniform::create([
            'employee_id' => $validatedData['employee_id'],
            'number_of_payments' => $validatedData['numberOfPayments'],
            'total_amount' => $validatedData['totalAmount'],
            'payments_per_payroll' => $validatedData['paymentPerPayroll'],
        ]);

        if ($validatedData['pantsPcs'] && $validatedData['pantsPrice'] && $validatedData['pantsSize']) {
             UniformPants::create([
                'uniform_id' => $uniform->id,
                'size' => $validatedData['pantsSize'],
                'pcs' => $validatedData['pantsPcs'],
                'price' => $validatedData['pantsPrice'],
            ]);
        }

        if ($validatedData['tShirtPcs'] && $validatedData['tShirtPrice'] && $validatedData['tShirtsize']) {
             UniformTshirt::create([
                'uniform_id' => $uniform->id,
                'size' => $validatedData['tShirtsize'],
                'pcs' => $validatedData['tShirtPcs'],
                'price' => $validatedData['tShirtPrice'],
            ]);
        }

        return response()->json([
            'message' => 'Uniform data saved successfully',
            'uniform' => $uniform,
        ], 201);
    }

    public function updateUniform(Request $request, $id)
    {
        $validatedData = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'numberOfPayments' => 'required|integer',
            'totalAmount' => 'required|integer',
            'paymentPerPayroll' => 'required|integer',
            'pantsPcs' => 'nullable|integer',
            'pantsPrice' => 'nullable|numeric',
            'pantsSize' => 'nullable|string',
            'tShirtPcs' => 'nullable|integer',
            'tShirtPrice' => 'nullable|numeric',
            'tShirtsize' => 'nullable|string'
        ]);

        $uniform = Uniform::findOrFail($id);

        // Update the uniform details
        $uniform->update([
            'employee_id' => $validatedData['employee_id'],
            'number_of_payments' => $validatedData['numberOfPayments'],
            'total_amount' => $validatedData['totalAmount'],
            'payments_per_payroll' => $validatedData['paymentPerPayroll'],
        ]);

        //Handle Pants Update

         if ($validatedData['pantsPcs'] && $validatedData['pantsPrice'] && $validatedData['pantsSize'])
         {
            $pants = $uniform->pants()->first();

            if ($pants) {
                $pants->update([
                    'size' => $validatedData['pantsSize'],
                    'pcs' => $validatedData['pantsPcs'],
                    'price' => $validatedData['pantsPrice'],
                ]);
            } else {
                UniformPants::create([
                    'uniform_id' => $uniform->id,
                    'size' => $validatedData['pantsSize'],
                    'pcs' => $validatedData['pantsPcs'],
                    'price' => $validatedData['pantsPrice'],
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

    /**
     * Display the specified resource.
     */
    public function show(Uniform $uniform)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Uniform $uniform)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Uniform $uniform)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Uniform $uniform)
    {
        //
    }
}
