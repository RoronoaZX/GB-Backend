<?php

namespace App\Http\Controllers;

use App\Models\EmployeeCreditProducts;
use App\Models\EmployeeCredits;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\TryCatch;

class EmployeeCreditsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Fetch credits data per cut off.
     */
    public function fetchCreditsPerCutOff($from, $to, $employee_id)
    {
        try {
            // Parse incoming date strings like "May 26, 2025"
            $fromDate = Carbon::parse($from)->startOfDay();
            $toDate = Carbon::parse($to)->endOfDay();

            // Get employee credits with related products and product info
            $credits = EmployeeCredits::with(['creditProducts.product', 'creditUserId'])
                ->where('credit_user_id', $employee_id)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->get();

            // Build response
            $response = [
                'employee_id' => $employee_id,
                'employee_name' => optional($credits->first()?->creditUserId)->full_name ?? 'N/A',
                'total_credits' => $credits->sum('total_amount'),
                'credit_records' => $credits->map(function ($credit) {
                    return [
                        'id' => $credit->id,
                        'branch_id' => $credit->branch_id,
                        'total_amount' => $credit->total_amount,
                        'description' => $credit->description,
                        'created_at' => $credit->created_at->format('Y-m-d H:i:s'),
                        'products' => $credit->creditProducts->map(function ($product) {
                            return [
                                'product_id' => $product->product_id,
                                'product_name' => optional($product->product)->name ?? 'N/A',
                                'price' => $product->price,
                                'pieces' => $product->pieces,
                                'total_price' => $product->price * $product->pieces,
                            ];
                        })
                    ];
                }),
            ];

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch employee credits',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    // public function fetchCreditsPerCutOff($from, $to, $employee_id)
    // {
    //     try {
    //         // Parse incoming date strings
    //         $fromDate = Carbon::parse($from)->startOfDay();
    //         $toDate = Carbon::parse($to)->endOfDay();

    //         // Fetch credits for a specific employee in the date range
    //         $creditIds = EmployeeCredits::where('credit_user_id', $employee_id)
    //             ->whereBetween('created_at', [$fromDate, $toD])
    //         // $credits = EmployeeCredits::with(['creditProducts.product', 'creditUserId'])
    //         //     ->where('credit_user_id', $employee_id)
    //         //     ->whereBetween('created_at', [$fromDate, $toDate])
    //         //     ->get();

    //         // Build structured response
    //         $response = [
    //             'employee_id' => $employee_id,
    //             'employee_name' => optional($credits->first()?->creditUserId)->full_name ?? 'N/A',
    //             'total_credits' => $credits->sum('total_amount'),
    //             'credit_records' => $credits->map(function ($credit) {
    //                 return [
    //                     'id' => $credit->id,
    //                     'branch_id' => $credit->branch_id,
    //                     'total_amount' => $credit->total_amount,
    //                     'description' => $credit->description,
    //                     'created_at' => $credit->created_at,
    //                     'products' => $credit->creditProducts->map(function ($product) {
    //                         return [
    //                             'product_id' => $product->product_id,
    //                             'product_name' => optional($product->product)->name,
    //                             'price' => $product->price,
    //                             'pieces' => $product->pieces,
    //                             'total_price' => $product->price * $product->pieces,
    //                         ];
    //                     })
    //                 ];
    //             })
    //         ];

    //         return response()->json($response);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'error' => 'Failed to fetch employee credits',
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }
    // public function fetchCreditsPerCutOff($from, $to, $employee_id)
    // {
    //     try {
    //         // Fetch credits for a specific employee in the date range
    //         $credits = EmployeeCredits::with(['creditProducts.product', 'creditUserId'])
    //             ->where('credit_user_id', $employee_id)
    //             ->whereBetween('created_at', [$from, $to])
    //             ->get();

    //         // Build structured response
    //         $response = [
    //             'employee_id' => $employee_id,
    //             'total_credits' => $credits->sum('total_amount'),
    //             'credit_records' => $credits->map(function ($credit) {
    //                 return [
    //                     'id' =>
    //                 ]
    //             })
    //         ]
    //     }
    // }

    public function saveCreditReport(Request $request)
    {
        $validatedData = $request->validate([
            'sales_report_id' => 'required|integer|exists:sales_reports,id',
            'user_id' => 'required|integer|exists:users,id',
            'branch_id' => 'required|integer|exists:branches,id',
            'credits' => 'required|array',
            'credits.*.credit_user_id' => 'required|integer',
            'credits.*.productName' => 'required|string|max:255',
            'credits.*.product_id' => 'required|integer|exists:products,id',
            'credits.*.price' => 'required|numeric|min:0',
            'credits.*.pieces' => 'required|integer|min:1',
            'credits.*.totalAmount' => 'required|numeric|min:0',
        ]);
        try {
            DB::beginTransaction(); // Start transaction to ensure data consistency

            // Save EmployeeCredit record
            $employeeCredit = EmployeeCredits::create([
                'sales_report_id' => $validatedData['sales_report_id'],
                'user_id' => $validatedData['user_id'],
                'branch_id' => $validatedData['branch_id'],
                'credit_user_id' => $validatedData['credits'][0]['credit_user_id'],
                'total_amount' => array_sum(array_column($validatedData['credits'], 'totalAmount')),
            ]);

            // Insert EmployeeCreditsProducts records
            foreach ($validatedData['credits'] as $credit) {
                EmployeeCreditProducts::create([
                    'employee_credits_id' => $employeeCredit->id,
                    'credit_user_id' => $credit['credit_user_id'],
                    'product_id' => $credit['product_id'],
                    'price' => $credit['price'],
                    'pieces' => $credit['pieces'],
                ]);
            }

            DB::commit(); // Commit transaction

            return response()->json([
                'message' => 'Employee credit report saved successfully.',
                'employee_credits_id' => $employeeCredit->id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction if an error occurs
            return response()->json([
                'message' => 'Failed to save credit report.',
                'error' => $e->getMessage()
            ], 500);
        }

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
    public function show(EmployeeCredits $employeeCredits)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeeCredits $employeeCredits)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeCredits $employeeCredits)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeCredits $employeeCredits)
    {
        //
    }
}
