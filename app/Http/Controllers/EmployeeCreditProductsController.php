<?php

namespace App\Http\Controllers;

use App\Models\EmployeeCreditProducts;
use Illuminate\Http\Request;

class EmployeeCreditProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function storeEmployeeCredits(Request $request)
{
    $validatedData = $request->validate([
        'employee_credits_id' => 'required|integer|exists:employee_credits,id',
        'credits' => 'required|array',
        'credits.*.credit_user_id' => 'required|integer',
        'credits.*.productName' => 'required|string|max:255',
        'credits.*.product_id' => 'required|integer|exists:products,id',
        'credits.*.price' => 'required|numeric|min:0',
        'credits.*.pieces' => 'required|integer|min:1',
        'credits.*.totalAmount' => 'required|numeric|min:0',
    ]);

    try {
        foreach ($validatedData['credits'] as $credit) {
            EmployeeCreditProducts::create([
                'employee_credits_id' => $validatedData['employee_credits_id'],
                'credit_user_id' => $credit['credit_user_id'],
                'product_name' => $credit['productName'],
                'product_id' => $credit['product_id'],
                'price' => $credit['price'],
                'pieces' => $credit['pieces'],
                'total_amount' => $credit['totalAmount'],
            ]);
        }

        return response()->json([
            'message' => 'Employee credits successfully stored!',
        ], 201);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Something went wrong!',
            'details' => $e->getMessage(),
        ], 500);
    }
}


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(EmployeeCreditProducts $employeeCreditProducts)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeeCreditProducts $employeeCreditProducts)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeCreditProducts $employeeCreditProducts)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeCreditProducts $employeeCreditProducts)
    {
        //
    }
}
