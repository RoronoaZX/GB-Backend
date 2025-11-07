<?php

namespace App\Http\Controllers;

use App\Models\IncentiveEmployeeReports;
use App\Models\InitialBakerreports;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IncentiveEmployeeReportsController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function getIncentiveEmployeeReportsPerDtrCutOff($from, $to, $employee_id)
    {
        $fromDate    = Carbon::parse($from)->startOfDay();
        $toDate      = Carbon::parse($to)->endOfDay();

        // Fetch all incentive reports for the employee withoin the given range
        $allIncentives = IncentiveEmployeeReports::where('employee_id', $employee_id)
                            ->whereBetween('created_at', [$fromDate, $toDate])
                            ->with('branch')
                            ->orderBy('created_at')
                            ->get();

        $filteredIncentives  = [];
        $processedDtrDates   = [];

        foreach ($allIncentives as $incentive) {
            // Convert to Manila timezone
            $createdAtManila = Carbon::parse($incentive->created_at)->setTimezone('Asia/Manila');

            // Calcualte DTR date (cutoff is 6 AM)
            $dtrDate = $createdAtManila->hour < 6
                        ? $createdAtManila->copy()->subDay()
                        : $createdAtManila->copy();

            $dtrDateString = $dtrDate->format('Y-m-d');

            // Avoid duplicates per DTR date
            if (isset($processedDtrDates[$dtrDateString])) {
                continue;
            }

            $processedDtrDates[$dtrDateString] = true;

            // Define DTR time window (UTC time)
            $dtrStart       = Carbon::parse("{$dtrDateString} 06:00:00", 'Asia/Manila')->setTimezone('UTC');
            $dtrEnd         = Carbon::parse($dtrDateString, 'Asia/Manila')
                                ->addDay()
                                ->setTime(5, 59, 59)
                                ->setTimezone('UTC');

            // Fetch confirmed Dough-category baker reports in that DTR window
            $bakerReports   = InitialBakerreports::where('branch_id', $incentive->branch_id)
                                ->where('status', 'confirmed')
                                ->where('recipe_category', 'Dough')
                                ->with('branchRecipe')
                                ->whereBetween('created_at', [$dtrStart, $dtrEnd])
                                ->get();

            // Attach computed and raw report data
            $incentive->baker_kilo_total     = $bakerReports->sum('kilo');
            $incentive->baker_reports        = $bakerReports;

            $filteredIncentives[] = $incentive;
        }

        return response()->json($filteredIncentives);
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
    public function show(IncentiveEmployeeReports $incentiveEmployeeReports)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(IncentiveEmployeeReports $incentiveEmployeeReports)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, IncentiveEmployeeReports $incentiveEmployeeReports)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IncentiveEmployeeReports $incentiveEmployeeReports)
    {
        //
    }
}
