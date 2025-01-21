<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchReport;
use App\Models\InitialBakerreports;
use App\Models\SalesReports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

    public function fetchBranchReport($branchId)
    {
        $branch = Branch::find($branchId);
        if (!$branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }

        // Fetch unique dates from both SalesReports and InitialBakerreports in UTC and convert to local time zone
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

        $branchReports = [];

        foreach ($dates as $date) {
            $carbonDate = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Manila');

            // AM Sales Reports: 6:00 AM - 10:00 PM
            $amSalesReports = SalesReports::where('branch_id', $branchId)
                ->whereBetween(DB::raw('CONVERT_TZ(created_at, "+00:00", "+08:00")'), [
                    $carbonDate->copy()->setTime(6, 0, 0)->toDateTimeString(),
                    $carbonDate->copy()->setTime(22, 0, 0)->toDateTimeString(),
                ])
                ->with(['user', 'branch', 'breadReports', 'selectaReports', 'softdrinksReports', 'expensesReports', 'denominationReports', 'creditReports', 'cakeSalesReports', 'otherProductsReports'])
                ->get();

            // PM Sales Reports: 10:01 PM - 5:59 AM
            $pmSalesReports = SalesReports::where('branch_id', $branchId)
                ->whereBetween(DB::raw('CONVERT_TZ(created_at, "+00:00", "+08:00")'), [
                    $carbonDate->copy()->setTime(22, 1, 0)->toDateTimeString(),
                    $carbonDate->copy()->addDay()->setTime(5, 59, 59)->toDateTimeString(),
                ])
                ->with(['user', 'branch', 'breadReports', 'selectaReports', 'softdrinksReports', 'expensesReports', 'denominationReports', 'creditReports', 'cakeSalesReports', 'otherProductsReports'])
                ->get();

            // AM Baker Reports: 6:00 AM - 5:00 PM
            $amBakerReports = InitialBakerreports::where('branch_id', $branchId)
                ->whereBetween(DB::raw('CONVERT_TZ(created_at, "+00:00", "+08:00")'), [
                    $carbonDate->copy()->setTime(6, 0, 0)->toDateTimeString(),
                    $carbonDate->copy()->setTime(17, 0, 0)->toDateTimeString(),
                ])
                ->with(['user', 'branch', 'breadBakersReports', 'ingredientBakersReports', 'fillingBakersReports', 'breadProductionReports', 'branchRecipe', ])
                ->get()
                ->map(function ($report) {
                    // Standardize property names for bread reports
                    $breadReports = $report->breadBakersReports->map(function ($breadReport) {
                        $breadReport->bread_production = $breadReport->bread_production; // No change needed
                        return $breadReport;
                    });

                    // Standardize property names for filling reports
                    $fillingReports = $report->fillingBakersReports->map(function ($fillingReport) {
                        $fillingReport->bread_production = $fillingReport->filling_production; // Rename to bread_production
                        unset($fillingReport->filling_production); // Remove the original property
                        return $fillingReport;
                    });

                    // Merge the collections
                    $report->combined_bakers_reports = $breadReports->merge($fillingReports);

                    return $report;
                    // $report->combined_bakers_reports = $report->breadBakersReports->merge($report->fillingBakersReports);
                    // return $report;
                });

            // PM Baker Reports: 6:00 PM - 5:59 AM
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
                    'sales_reports_id' => $amSalesReports->pluck('id')->first(), // Retrieve the first ID
                    'sales_reports' => $amSalesReports,
                    'baker_reports' => $amBakerReports,
                    'date' => $carbonDate->toDateString(),
                    'branch_name' => $branch->name,
                ],
                'PM' => [
                    'sales_reports_id' => $pmSalesReports->pluck('id')->first(), // Retrieve the first ID
                    'sales_reports' => $pmSalesReports,
                    'baker_reports' => $pmBakerReports,
                    'date' => $carbonDate->toDateString(),
                    'branch_name' => $branch->name,
                ],
            ];
        }

        if (!empty($branchReports)) {
            return response()->json($branchReports);
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
    //         $carbonDate = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Manila');

    //         // AM reports: 6:00 AM - 10:00 PM
    //         $amSalesReports = SalesReports::where('branch_id', $branchId)
    //             ->whereDate(DB::raw('CONVERT_TZ(created_at, "+00:00", "+08:00")'), $carbonDate)
    //             ->get()
    //             ->filter(function ($report) {
    //                 $localTime = Carbon::parse($report->created_at)->setTimezone('Asia/Manila');
    //                 $hour = $localTime->hour;
    //                 $minute = $localTime->minute;
    //                 return ($hour > 6 || ($hour == 6 && $minute > 0)) && $hour < 22; // It represents 6:01 AM to 9:59 PM
    //             })
    //             ->load(['user', 'branch', 'breadReports', 'selectaReports', 'softdrinksReports', 'expensesReports', 'denominationReports', 'creditReports', 'cakeSalesReports', 'otherProductsReports']);

    //         $amBakerReports = InitialBakerreports::where('branch_id', $branchId)
    //             ->whereDate(DB::raw('CONVERT_TZ(created_at, "+00:00", "+08:00")'), $carbonDate)
    //             ->get()
    //             ->filter(function ($report) {
    //                 $localTime = Carbon::parse($report->created_at)->setTimezone('Asia/Manila');
    //                 $hour = $localTime->hour;
    //                 $minute = $localTime->minute;
    //                 return ($hour > 6 || ($hour == 6 && $minute > 0)) && $hour < 18; // It represents 6:01 AM to 5:59 PM,
    //                 // return $hour >= 6 && $hour < 17;
    //             })
    //             ->load(['user', 'branch', 'breadBakersReports', 'ingredientBakersReports', 'fillingBakersReports', 'breadProductionReports', 'branchRecipe']);

    //         // PM reports: 11:00 PM - 5:00 AM
    //         $pmSalesReports = SalesReports::where('branch_id', $branchId)
    //             ->whereDate(DB::raw('CONVERT_TZ(created_at, "+00:00", "+08:00")'), $carbonDate)
    //             ->get()
    //             ->filter(function ($report) {
    //                 $localTime = Carbon::parse($report->created_at)->setTimezone('Asia/Manila');
    //                 $hour = $localTime->hour;
    //                 return $hour >= 23 || $hour < 6;
    //             })
    //             ->load(['user', 'branch', 'breadReports', 'selectaReports', 'softdrinksReports', 'expensesReports', 'denominationReports', 'creditReports', 'cakeSalesReports', 'otherProductsReports']);

    //         $pmBakerReports = InitialBakerreports::where('branch_id', $branchId)
    //             ->whereDate(DB::raw('CONVERT_TZ(created_at, "+00:00", "+08:00")'), $carbonDate)
    //             ->get()
    //             ->filter(function ($report) {
    //                 $localTime = Carbon::parse($report->created_at)->setTimezone('Asia/Manila');
    //                 $hour = $localTime->hour;
    //                 return $hour >= 18 || $hour < 6;
    //             })
    //             ->load(['user', 'branch', 'breadBakersReports', 'ingredientBakersReports', 'fillingBakersReports', 'breadProductionReports', 'branchRecipe']);

    //         $branchReports[] = [
    //             'date' => $carbonDate->toDateString(),
    //             'AM' => [
    //                 'sales_reports' => $amSalesReports,
    //                 'baker_reports' => $amBakerReports,
    //                 'date' => $carbonDate->toDateString(),
    //                 'branch_name' => $branch->name,
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

    public function fetchBranchSalesReport($branchId)
    {
        $branch = Branch::find($branchId);
        if (!$branch) {
            return response()->json(['message' => 'Branch not found'], 404);
        }

         // Fetch unique dates from both SalesReports and InitialBakerreports in UTC and convert to local time zone
         $dates = DB::table('sales_reports')
            ->select(DB::raw('DATE(CONVERT_TZ(created_at, "+00:00", "+08:00")) as date'))
            ->where('branch_id', $branchId)
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->pluck('date');

        $branchReports = [];

        foreach ($dates as $date) {
            $carbonDate = Carbon::createFromFormat('Y-m-d', $date, 'Asia/Manila');

            // AM reports: 6:00 AM - 10:00 PM

            // AM reports: 6:00 AM - 10:00 PM
            $amSalesReports = SalesReports::where('branch_id', $branchId)
                ->whereDate(DB::raw('CONVERT_TZ(created_at, "+00:00", "+08:00")'), $carbonDate)
                ->get()
                ->filter(function ($report) {
                    $localTime = Carbon::parse($report->created_at)->setTimezone('Asia/Manila');
                    $hour = $localTime->hour;
                    return $hour >= 6 && $hour < 22;
                })
                ->load(['user', 'branch', 'breadReports', 'selectaReports', 'softdrinksReports', 'expensesReports', 'denominationReports', 'creditReports', 'cakeSalesReports', 'otherProductsReports']);

                 // PM reports: 11:00 PM - 5:00 AM
            $pmSalesReports = SalesReports::where('branch_id', $branchId)
            ->whereDate(DB::raw('CONVERT_TZ(created_at, "+00:00", "+08:00")'), $carbonDate)
            ->get()
            ->filter(function ($report) {
                $localTime = Carbon::parse($report->created_at)->setTimezone('Asia/Manila');
                $hour = $localTime->hour;
                return $hour >= 23 || $hour < 6;
            })
            ->load(['user', 'branch', 'breadReports', 'selectaReports', 'softdrinksReports', 'expensesReports', 'denominationReports', 'creditReports', 'cakeSalesReports', 'otherProductsReports']);

            $branchReports[] = [
                'date' => $carbonDate->toDateString(),
                'AM' => [
                    'sales_reports' => $amSalesReports,
                    'date' => $carbonDate->toDateString(),
                    'branch_name' => $branch->name,
                ],
                'PM' => [
                    'sales_reports' => $pmSalesReports,
                    'date' => $carbonDate->toDateString(),
                    'branch_name' => $branch->name,
                ]
            ];
        }


        if (!empty($branchReports)) {
            return response()->json($branchReports);
        } else {
            return response()->json(['message' => 'No reports found'], 404);
        }
    }

//     public function fetchBranchReport($branchId)
// {
//     $branch = Branch::find($branchId);
//     if (!$branch) {
//         return response()->json(['message' => 'Branch not found'], 404);
//     }

//     $latestBranchReport = SalesReports::where('branch_id', $branchId)
//         ->orderBy('created_at', 'desc')
//         ->get();

//     $branchReports = [];

//     foreach ($latestBranchReport as $branchReport) {
//         $date = $branchReport->created_at->toDateString();
//         $time = $branchReport->created_at->toTimeString();
//         $hour = $branchReport->created_at->hour;


//         $period = $hour < 12 ? 'AM' : 'PM';

//         $salesReports = SalesReports::where('branch_id', $branchId)
//             ->whereDate('created_at', $date)
//             ->whereTime('created_at', '>=', $period == 'AM' ? '00:00:00' : '12:00:00')
//             ->whereTime('created_at', '<', $period == 'AM' ? '12:00:00' : '23:59:59')
//             ->with(['user', 'breadReports', 'selectaReports', 'softdrinksReports', 'expensesReports', 'denominationReports'])
//             ->get();

//         $bakerReports = InitialBakerreports::where('branch_id', $branchId)
//             ->whereDate('created_at', $date)
//             ->whereTime('created_at', '>=', $period == 'AM' ? '00:00:00' : '12:00:00')
//             ->whereTime('created_at', '<', $period == 'AM' ? '12:00:00' : '23:59:59')
//             ->with(['user', 'breadBakersReports', 'ingredientBakersReports', 'fillingBakersReports', 'breadProductionReports', 'recipe'])
//             ->get();

//         if ($salesReports->isNotEmpty() || $bakerReports->isNotEmpty()) {
//             $branchReports[$period][] = [
//                 'date' => $date,
//                 'time' => $time,
//                 'branch_name' => $branch->name,
//                 'sales_reports' => $salesReports,
//                 'baker_reports' => $bakerReports,
//             ];
//         }
//     }

//     $data = [
//         'branch_id' => $branchId,
//         'reports' => $branchReports
//     ];

//     if (!empty($branchReports)) {
//         return response()->json($branchReports);
//     } else {
//         return response()->json(['message' => 'No reports found'], 404);
//     }
// }


    // public function fetchBranchReport($branchId)
    // {

    //     $branch = Branch::find($branchId);
    //     if (!$branch) {
    //         return response()->json(['message' => 'Branch not found'], 404);
    //     }

    //     $latestBranchReport = SalesReports::where('branch_id', $branchId)
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     $branchReports = [];

    //     foreach ($latestBranchReport as $branchReport) {
    //         $date = $branchReport->created_at->toDateString();
    //         $time = $branchReport->created_at->toTimeString();


    //         $salesReports = SalesReports::where('branch_id', $branchId)
    //             ->whereDate('created_at', $date)
    //             ->with(['user','breadReports', 'selectaReports', 'softdrinksReports', 'expensesReports', 'denominationReports'])
    //             ->get();

    //         $bakerReports = InitialBakerreports::where('branch_id', $branchId)
    //             ->whereDate('created_at', $date)
    //             ->with(['user', 'breadBakersReports', 'ingredientBakersReports', 'fillingBakersReports', 'breadProductionReports', 'recipe'])
    //             ->get();

    //         $branchReports[] = [
    //             'date' => $date,
    //             'time' => $time,
    //             'branch_name' => $branch->name,
    //             'sales_reports' => $salesReports,
    //             'baker_reports' => $bakerReports,
    //         ];
    //     }


    //     $data = [
    //         'branch_id' => $branchId,
    //         'reports' => $branchReports
    //     ];

    //     if (!empty($branchReports)) {
    //         return response()->json($branchReports);
    //     } else {
    //         return response()->json(['message' => 'No reports found'], 404);
    //     }
    // }

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
