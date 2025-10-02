<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchReport;
use App\Models\InitialBakerreports;
use App\Models\SalesReports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Client\ResponseSequence;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BranchReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get all unique branch IDs
        $branches = InitialBakerreports::select('branch_id')
                        ->distinct()
                        ->get();

        $data = [];

        foreach ($branches as $branch) {
            $branchId = $branch->branch_id;

            // Get the latest report date for each branch
            $latestBranchReport = InitialBakerreports::where('branch_id', $branchId)
                                   ->orderBy('created_at', 'desc')
                                   ->get();

            $branchReports = [];

            foreach ($latestBranchReport as $bakerReports) {
                $date = $bakerReports->created_at->toDateString();

                $salesReports = SalesReports::where('branch_id', $branchId)
                                ->whereDate('created_at' , $date)
                                ->with(['breadReports', 'selectaReports', 'softdrinksReports', 'expensesReports', 'denominationReports'])
                                ->get();

                $bakerReports = InitialBakerreports::where('branch_id', $branchId)
                                ->whereDate('created_at', $date)
                                ->with(['breadBakersReports', 'ingredientBakersReports', 'fillingBakersReports', 'breadProductionReports'])
                                ->get();

                $branchReports[] = [
                    'date' => $date,
                    'sales_reports' => $salesReports,
                    'baker_reports' => $bakerReports
                ];
            }

            $data[] = [
                'branch_id' => $branchId,
                'reports' => $branchReports
            ];
        }

        if (!empty($data)) {
            return response()->json($data);
        } else {
            return response()->json(['message' => 'No reports found'], 404);
        }
    }

    // public function fetchBranchReport($branchId)
    // {
    //     $branch = Branch::find($branchId);
    //     if (!$branch) {
    //         return response()->json(['message' => 'Branch not found'], 404);
    //     }

    //     // Fetch unique dates from both SalesReports and InitialBakerreports in UTC and convert to local time zone
    //     $dates = DB::table('sales_reports')
    //         ->select(DB::raw('DATE(CONVERT_TZ(created_at, "+00:00", "+08:00")) as date'))
    //         ->where('branch_id', $branchId)
    //         ->union(
    //             DB::table('initial_bakerreports')
    //                 ->select(DB::raw('DATE(CONVERT_TZ(created_at, "+00:00", "+08:00")) as date'))
    //                 ->where('branch_id', $branchId)
    //         )
    //         ->union(
    //             DB::table('cake_sales_reports')
    //                 ->join('sales_reports', 'cake_sales_reports.sales_report_id', '=', 'sales_reports.id')
    //                 ->select(DB::raw('DATE(CONVERT_TZ(cake_sales_reports.created_at, "+00:00", "+08:00")) as date'))
    //                 ->where('sales_reports.branch_id', $branchId)
    //         )
    //         ->groupBy('date')
    //         ->orderBy('date', 'desc')
    //         ->pluck('date');

    //     $branchReports = [];

    //     foreach ($dates as $date) {
    //         // Convert the date string back to a Carbon instance in the Philippine timezone
    //         $carbonDate = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Manila');

    //         // Fetch Sales Reports for AM and PM
    //         $amSalesReports = SalesReports::where('branch_id', $branchId)
    //             ->whereDate(DB::raw('CONVERT_TZ(created_at, "+00:00", "+08:00")'), $carbonDate)
    //             ->get()
    //             ->filter(function($report) {
    //                 $localTime = Carbon::parse($report->created_at)->setTimezone('Asia/Manila');
    //                 return $localTime->hour < 12;
    //             })
    //             ->load(['user', 'branch', 'breadReports', 'selectaReports', 'softdrinksReports', 'expensesReports', 'denominationReports', 'creditReports', 'cakeSalesReports', 'otherProductsReports']);

    //         $pmSalesReports = SalesReports::where('branch_id', $branchId)
    //             ->whereDate(DB::raw('CONVERT_TZ(created_at, "+00:00", "+08:00")'), $carbonDate)
    //             ->get()
    //             ->filter(function($report) {
    //                 $localTime = Carbon::parse($report->created_at)->setTimezone('Asia/Manila');
    //                 return $localTime->hour >= 12;
    //             })
    //             ->load(['user', 'branch', 'breadReports', 'selectaReports', 'softdrinksReports', 'expensesReports', 'denominationReports', 'creditReports', 'cakeSalesReports', 'otherProductsReports']);

    //         // Fetch Baker Reports for AM and PM
    //         $amBakerReports = InitialBakerreports::where('branch_id', $branchId)
    //             ->whereDate(DB::raw('CONVERT_TZ(created_at, "+00:00", "+08:00")'), $carbonDate)
    //             ->get()
    //             ->filter(function($report) {
    //                 $localTime = Carbon::parse($report->created_at)->setTimezone('Asia/Manila');
    //                 return $localTime->hour < 12;
    //             })
    //             ->load(['user','branch', 'breadBakersReports', 'ingredientBakersReports', 'fillingBakersReports', 'breadProductionReports', 'branchRecipe']);

    //         $pmBakerReports = InitialBakerreports::where('branch_id', $branchId)
    //             ->whereDate(DB::raw('CONVERT_TZ(created_at, "+00:00", "+08:00")'), $carbonDate)
    //             ->get()
    //             ->filter(function($report) {
    //                 $localTime = Carbon::parse($report->created_at)->setTimezone('Asia/Manila');
    //                 return $localTime->hour >= 12;
    //             })
    //             ->load(['user', 'branch', 'breadBakersReports', 'ingredientBakersReports', 'fillingBakersReports', 'breadProductionReports', 'branchRecipe']);

    //         // Group the reports by date
    //         $branchReports[] = [
    //             'date' => $carbonDate->toDateString(),

    //             'AM' => [
    //                 'sales_reports' => $amSalesReports,
    //                 'baker_reports' => $amBakerReports,
    //                 'date' => $carbonDate->toDateString(),
    //             'branch_name' => $branch->name,
    //             ],
    //             'PM' => [
    //                 'sales_reports' => $pmSalesReports,
    //                 'baker_reports' => $pmBakerReports,
    //                 'date' => $carbonDate->toDateString(),
    //                 'branch_name' => $branch->name,
    //             ],
    //         ];
    //     }

    //     if (!empty($branchReports)) {
    //         return response()->json($branchReports);
    //     } else {
    //         return response()->json(['message' => 'No reports found'], 404);
    //     }
    // }

    //------------------------//
    // this is the final codes//
    //------------------------//
    public function fetchBranchReport($branchId)
    {
        $branch = Branch::find($branchId);
        if (!$branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }

        // Fetch unique dates from all reports
        $dates = DB::table('sales_reports')
            ->select(DB::raw('DATE(CONVERT_TZ(created_at, "+00:00", "+08:00")) as date'))
            ->where('branch_id', $branchId)
            ->union(
                DB::table('initial_bakerreports')
                    ->select(DB::raw('DATE(CONVERT_TZ(created_at, "+00:00", "+08:00")) as date'))
                    ->where('branch_id', $branchId)
            )
            ->union(
                DB::table('cake_sales_reports')
                    ->join('sales_reports', 'cake_sales_reports.sales_report_id', '=', 'sales_reports.id')
                    ->select(DB::raw('DATE(CONVERT_TZ(cake_sales_reports.created_at, "+00:00", "+08:00")) as date'))
                    ->where('sales_reports.branch_id', $branchId)
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->pluck('date');

        // Setup pagination
        $page = request()->get('page', 1); // Default to page 1 if no page param
        $perPage = request()->get('per_page', 5); // Default 5 items per page

        $allDates = $dates;  // already ordered DESC from your query
        $paginatedDates = ($perPage == 0) ? $allDates :  (new Collection($dates))->forPage($page, $perPage);

        $branchReports = [];

        foreach ($paginatedDates as $date) {
            $carbonDate = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Manila');

            // Fetch AM Sales Reports
            $amSalesReports = SalesReports::where('branch_id', $branchId)
                ->whereBetween(DB::raw('CONVERT_TZ(created_at, "+00:00", "+08:00")'), [
                    $carbonDate->copy()->setTime(6, 0, 0)->toDateTimeString(),
                    $carbonDate->copy()->setTime(22, 0, 0)->toDateTimeString(),
                ])
                ->with(['user', 'branch', 'breadReports', 'selectaReports', 'softdrinksReports', 'expensesReports', 'denominationReports', 'creditReports', 'cakeSalesReports', 'otherProductsReports'])
                ->get();

            // Fetch PM Sales Reports
            $pmSalesReports = SalesReports::where('branch_id', $branchId)
                ->whereBetween(DB::raw('CONVERT_TZ(created_at, "+00:00", "+08:00")'), [
                    $carbonDate->copy()->setTime(22, 1, 0)->toDateTimeString(),
                    $carbonDate->copy()->addDay()->setTime(5, 59, 59)->toDateTimeString(),
                ])
                ->with(['user', 'branch', 'breadReports', 'selectaReports', 'softdrinksReports', 'expensesReports', 'denominationReports', 'creditReports', 'cakeSalesReports', 'otherProductsReports'])
                ->get();

            // Fetch AM Baker Reports
            $amBakerReports = InitialBakerreports::where('branch_id', $branchId)
                ->whereBetween(DB::raw('CONVERT_TZ(created_at, "+00:00", "+08:00")'), [
                    $carbonDate->copy()->setTime(6, 0, 0)->toDateTimeString(),
                    $carbonDate->copy()->setTime(17, 0, 0)->toDateTimeString(),
                ])
                ->with(['user', 'branch', 'breadBakersReports', 'ingredientBakersReports', 'fillingBakersReports', 'breadProductionReports', 'branchRecipe'])
                ->get()
                ->map(function ($report) {
                    $breadReports = $report->breadBakersReports->map(function ($breadReport) {
                        $breadReport->bread_production = $breadReport->bread_production;
                        return $breadReport;
                    });

                    $fillingReports = $report->fillingBakersReports->map(function ($fillingReport) {
                        $fillingReport->bread_production = $fillingReport->filling_production;
                        unset($fillingReport->filling_production);
                        return $fillingReport;
                    });

                    $report->combined_bakers_reports = $breadReports->merge($fillingReports);
                    return $report;
                });

            // Fetch PM Baker Reports
            $pmBakerReports = InitialBakerreports::where('branch_id', $branchId)
                ->whereBetween(DB::raw('CONVERT_TZ(created_at, "+00:00", "+08:00")'), [
                    $carbonDate->copy()->setTime(18, 0, 0)->toDateTimeString(),
                    $carbonDate->copy()->addDay()->setTime(5, 59, 59)->toDateTimeString(),
                ])
                ->with(['user', 'branch', 'breadBakersReports', 'ingredientBakersReports', 'fillingBakersReports', 'breadProductionReports', 'branchRecipe'])
                ->get()
                ->map(function ($report) {
                    $report->combined_bakers_reports = $report->breadBakersReports->merge($report->fillingBakersReports);
                    return $report;
                });

            $branchReports[] = [
                'date' => $carbonDate->toDateString(),
                'AM' => [
                    'sales_reports_id'   => $amSalesReports->pluck('id')->first(),
                    'sales_reports'      => $amSalesReports,
                    'baker_reports'      => $amBakerReports,
                    'date'               => $carbonDate->toDateString(),
                    'branch_name'        => $branch->name,
                ],
                'PM' => [
                    'sales_reports_id'   => $pmSalesReports->pluck('id')->first(),
                    'sales_reports'      => $pmSalesReports,
                    'baker_reports'      => $pmBakerReports,
                    'date'               => $carbonDate->toDateString(),
                    'branch_name'        => $branch->name,
                ],
            ];
        }

        // Return paginated or full result

        if ($perPage == 0) {
            return response()->json([
                'data'           => $branchReports,
                'total'          => count($branchReports),
                'per_page'       => count($branchReports),
                'current_page'   => 1,
                'last_page'      => 1
            ]);
        } else {

        // Create manual pagination
        $paginator = new LengthAwarePaginator(
            $branchReports,
            count($dates),
            $perPage,
            $page,
            ['path' => url()->current()]
        );

        }

        return response()->json($paginator);
    }

public function fetchBranchSalesReport($branchId)
{
    $branch = Branch::find($branchId);
    if (!$branch) {
        return response()->json(['message' => 'Branch not found'], 404);
    }

    // Fetch unique dates from sales_reports (converted to Asia/Manila timezone)
    $dates = DB::table('sales_reports')
                ->select(DB::raw('DATE(CONVERT_TZ(created_at, "+00:00", "+08:00")) as date'))
                ->where('branch_id', $branchId)
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->pluck('date');

    // Setup pagination
    $page = request()->get('page', 1);
    $perPage = request()->get('per_page', 5);

    $allDates = $dates; // already ordered DESC form your query
    $paginatedDates = ($perPage == 0) ? $allDates : (new Collection($dates))->forPage($page, $perPage)->values(); // Reset index

    $branchReports = [];

    foreach ($paginatedDates as $date) {
        $carbonDate = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Manila');

        // AM Sales Reports: 6:00 AM - 10:00 PM
        $amSalesReports = SalesReports::where('branch_id', $branchId)
            ->whereBetween(DB::raw('CONVERT_TZ(created_at, "+00:00", "+08:00")'), [
                $carbonDate->copy()->setTime(6, 0, 0)->toDateTimeString(),
                $carbonDate->copy()->setTime(22, 0, 0)->toDateTimeString(),
            ])
            ->with([
                'user', 'branch', 'breadReports', 'selectaReports', 'softdrinksReports',
                'expensesReports', 'denominationReports', 'creditReports',
                'cakeSalesReports', 'otherProductsReports'
            ])
            ->get();

        // PM Sales Reports: 10:01 PM - 5:59 AM
        $pmSalesReports = SalesReports::where('branch_id', $branchId)
            ->whereBetween(DB::raw('CONVERT_TZ(created_at, "+00:00", "+08:00")'), [
                $carbonDate->copy()->setTime(22, 1, 0)->toDateTimeString(),
                $carbonDate->copy()->addDay()->setTime(5, 59, 59)->toDateTimeString(),
            ])
            ->with([
                'user', 'branch', 'breadReports', 'selectaReports', 'softdrinksReports',
                'expensesReports', 'denominationReports', 'creditReports',
                'cakeSalesReports', 'otherProductsReports'
            ])
            ->get();

        $branchReports[] = [
            'date'   => $carbonDate->toDateString(),
            'AM'     => [
                        'sales_reports'  => $amSalesReports,
                        'date'           => $carbonDate->toDateString(),
                        'branch_name'    => $branch->name,
                    ],
            'PM'    => [
                        'sales_reports'  => $pmSalesReports,
                        'date'           => $carbonDate->toDateString(),
                        'branch_name'    => $branch->name,
                    ]
        ];
    }

    // Return paginated or full result

    if ($perPage == 0) {
        return response()->json([
            'data'           => $branchReports,
            'total'          => count($branchReports),
            'per_page'       => count($branchReports),
            'current_page'   => 1,
            'last_page'      => 1
        ]);
    } else {
        // Create manual pagination
        $paginator = new LengthAwarePaginator(
            $branchReports,
            count($dates),       // total number of all dates
            $perPage,
            $page,
            ['path'         => url()->current()] // for next_page_url, prev_page_url, etc.
        );
    }


    return response()->json($paginator);
}

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BranchReport  $branchReport
     * @return \Illuminate\Http\Response
     */
    public function show(BranchReport $branchReport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BranchReport  $branchReport
     * @return \Illuminate\Http\Response
     */
    public function edit(BranchReport $branchReport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BranchReport  $branchReport
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BranchReport $branchReport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BranchReport  $branchReport
     * @return \Illuminate\Http\Response
     */
    public function destroy(BranchReport $branchReport)
    {
        //
    }
}
