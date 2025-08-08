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

    // private function getPayrollPeriodKey(Carbon $date): string
    // {
    //     $day = $date->day;

    //     // Period: From the 11th to the 25th of the month
    //     if ($day >= 11 && $day <= 25) {
    //         $startDate = $date->copy()->day(11);
    //         $endDate = $date->copy()->day(25);
    //         return $startDate->toDateString() . '_' . $endDate->toDateString();
    //     }

    //     // Period: From the 26th of the previous month to the 10th of the current month
    //     if ($day <= 10) {
    //         $endDate = $date->copy()->day(10);
    //         $startDate = $date->copy()->subMonth()->day(26);
    //         return $startDate->toDateString() . '_' . $endDate->toDateString();
    //     } else { // $day >= 26
    //         $startDate = $date->copy()->day(26);
    //         $endDate = $date->copy()->addMonth()->day(10);
    //         return $startDate->toDateString() . '_' . $endDate->toDateString();
    //     }
    // }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    // public function getIncentiveEmployeeReportsPerDtrCutOff($from, $to, $employee_id)
    // {
    //     $fromDate = Carbon::parse($from)->startOfDay();
    //     $toDate = Carbon::parse($to)->endOfDay();

    //     // Fetch all incentive reports for the employee within the date range
    //     $allIncentives = IncentiveEmployeeReports::where('employee_id', $employee_id)
    //         ->whereBetween('created_at', [$fromDate, $toDate])
    //         ->orderBy('created_at') // Ensure earliest first
    //         ->get();

    //     $filteredIncentives = [];

    //     // Use a map to track the first entry per shift group (date + shift type)
    //     $seenShifts = [];

    //     foreach ($allIncentives as $incentive) {
    //         $created = Carbon::parse($incentive->created_at)->setTimezone('Asia/Manila');

    //         $dayShiftStart = $created->copy()->setTime(6, 0, 0);
    //         $dayShiftEnd = $created->copy()->setTime(22, 0, 0);

    //         $isDayShift = $created->between($dayShiftStart, $dayShiftEnd);
    //         $shift = $isDayShift ? 'day' : 'offshift';

    //         // Key: date + shift type
    //         $groupKey = $created->format('Y-m-d') . '_' . $shift;

    //         if (!isset($seenShifts[$groupKey])) {
    //             $seenShifts[$groupKey] = true;
    //             $filteredIncentives[] = $incentive;
    //         }
    //     }

    //     return response()->json($filteredIncentives);
    // }

    // public function getIncentiveEmployeeReportsPerDtrCutOff($from, $to, $employee_id)
    // {
    //     $fromDate = Carbon::parse($from)->startOfDay();
    //     $toDate = Carbon::parse($to)->endOfDay();

    //     $allIncentives = IncentiveEmployeeReports::where('employee_id', $employee_id)
    //         ->whereBetween('created_at', [$fromDate, $toDate])
    //         ->orderBy('created_at')
    //         ->get();

    //     $filteredIncentives = [];
    //     $seenShifts = [];

    //     foreach ($allIncentives as $incentive) {
    //         $created = Carbon::parse($incentive->created_at)->setTimezone('Asia/Manila');

    //         // Determine shift type based on time
    //         $hour = (int) $created->format('H');
    //         $shift = ($hour >= 22 || $hour < 6) ? 'night' : 'day';

    //         // For night shift, use the previous day as base date if before 6AM
    //         $shiftDate = ($shift === 'night' && $hour < 6)
    //             ? $created->copy()->subDay()->format('Y-m-d')
    //             : $created->format('Y-m-d');

    //         $groupKey = $shiftDate . '_' . $shift;

    //         if (!isset($seenShifts[$groupKey])) {
    //             $seenShifts[$groupKey] = true;

    //             // Determine time window for shift
    //             if ($shift === 'day') {
    //                 $shiftStart = Carbon::parse($shiftDate . ' 06:00:00', 'Asia/Manila');
    //                 $shiftEnd = Carbon::parse($shiftDate . ' 21:59:59', 'Asia/Manila');
    //             } else {
    //                 $shiftStart = Carbon::parse($shiftDate . ' 22:00:00', 'Asia/Manila');
    //                 $shiftEnd = Carbon::parse($shiftDate, 'Asia/Manila')->addDay()->setTime(5, 59, 59);
    //             }

    //             // Fetch InitialBakerreports within the shift window
    //             $bakerReports = InitialBakerreports::where('branch_id', $incentive->branch_id)
    //                 ->whereBetween('created_at', [$shiftStart, $shiftEnd])
    //                 ->get();

    //             // Attach to incentive
    //             $incentive->baker_reports = $bakerReports;

    //             $filteredIncentives[] = $incentive;
    //         }
    //     }

    //     return response()->json($filteredIncentives);
    // }

//     public function getIncentiveEmployeeReportsPerDtrCutOff($from, $to, $employee_id)
// {
//     $fromDate = Carbon::parse($from)->startOfDay();
//     $toDate = Carbon::parse($to)->endOfDay();

//     $allIncentives = IncentiveEmployeeReports::where('employee_id', $employee_id)
//         ->whereBetween('created_at', [$fromDate, $toDate])
//         ->orderBy('created_at')
//         ->get();

//     $filteredIncentives = [];
//     $seenShifts = [];

//     foreach ($allIncentives as $incentive) {
//         $created = Carbon::parse($incentive->created_at)->setTimezone('Asia/Manila');

//         $hour = (int) $created->format('H');
//         $shift = ($hour >= 22 || $hour < 6) ? 'night' : 'day';

//         $shiftDate = ($shift === 'night' && $hour < 6)
//             ? $created->copy()->subDay()->format('Y-m-d')
//             : $created->format('Y-m-d');

//         $groupKey = $shiftDate . '_' . $shift;

//         if (!isset($seenShifts[$groupKey])) {
//             $seenShifts[$groupKey] = true;

//             if ($shift === 'day') {
//                 $shiftStart = Carbon::parse($shiftDate . ' 06:00:00', 'Asia/Manila');
//                 $shiftEnd = Carbon::parse($shiftDate . ' 21:59:59', 'Asia/Manila');
//             } else {
//                 $shiftStart = Carbon::parse($shiftDate . ' 22:00:00', 'Asia/Manila');
//                 $shiftEnd = Carbon::parse($shiftDate, 'Asia/Manila')->addDay()->setTime(5, 59, 59);
//             }

//             // Fetch only confirmed InitialBakerreports
//             $bakerReports = InitialBakerreports::where('branch_id', $incentive->branch_id)
//                 ->where('status', 'confirmed')
//                 ->whereBetween('created_at', [$shiftStart, $shiftEnd])
//                 ->get();

//             $incentive->baker_reports = $bakerReports;

//             $filteredIncentives[] = $incentive;
//         }
//     }

//     return response()->json($filteredIncentives);
// }

    // public function getIncentiveEmployeeReportsPerDtrCutOff($from, $to, $employee_id)
    // {
    //     $fromDate = Carbon::parse($from)->startOfDay();
    //     $toDate = Carbon::parse($to)->endOfDay();

    //     // Fetch all incentive reports for the employee within the date range
    //     $allIncentives = IncentiveEmployeeReports::where('employee_id', $employee_id)
    //         ->whereBetween('created_at', [$fromDate, $toDate])
    //         ->orderBy('created_at')
    //         ->get();

    //     $filteredIncentives = [];
    //     $seenShifts = [];

    //     foreach ($allIncentives as $incentive) {
    //         // FIX: Parse as UTC then convert to Asia/Manila
    //         $created = Carbon::parse($incentive->created_at, 'UTC')->setTimezone('Asia/Manila');

    //         // Determine shift type based on hour
    //         $hour = (int) $created->format('H');
    //         $shift = ($hour >= 22 || $hour < 6) ? 'night' : 'day';

    //         // If night shift before 6AM, shift belongs to previous day
    //         $shiftDate = ($shift === 'night' && $hour < 6)
    //             ? $created->copy()->subDay()->format('Y-m-d')
    //             : $created->format('Y-m-d');

    //         $groupKey = $shiftDate . '_' . $shift;

    //         // Prevent duplicates per employee per shift
    //         if (!isset($seenShifts[$groupKey])) {
    //             $seenShifts[$groupKey] = true;

    //             // Define shift time window
    //             if ($shift === 'day') {
    //                 $shiftStart = Carbon::parse($shiftDate . ' 06:00:00', 'Asia/Manila');
    //                 $shiftEnd = Carbon::parse($shiftDate . ' 21:59:59', 'Asia/Manila');
    //             } else {
    //                 $shiftStart = Carbon::parse($shiftDate . ' 22:00:00', 'Asia/Manila');
    //                 $shiftEnd = Carbon::parse($shiftDate, 'Asia/Manila')->addDay()->setTime(5, 59, 59);
    //             }

    //             // Fetch confirmed InitialBakerreports within this shift window
    //             $bakerReports = InitialBakerreports::where('branch_id', $incentive->branch_id)
    //                 ->where('status', 'confirmed')
    //                 ->whereBetween('created_at', [$shiftStart, $shiftEnd])
    //                 ->get();

    //             // Optional: Attach total kilo
    //             $incentive->baker_kilo_total = $bakerReports->sum('kilo');

    //             // Attach full list of reports
    //             $incentive->baker_reports = $bakerReports;

    //             // Add to output
    //             $filteredIncentives[] = $incentive;
    //         }
    //     }

    //     return response()->json($filteredIncentives);
    // }

    // public function getIncentiveEmployeeReportsPerDtrCutOff($from, $to, $employee_id)
    // {
    //     $fromDate = Carbon::parse($from)->startOfDay();
    //     $toDate = Carbon::parse($to)->endOfDay();

    //     // Fetch all incentive reports for the employee within the date range
    //     $allIncentives = IncentiveEmployeeReports::where('employee_id', $employee_id)
    //         ->whereBetween('created_at', [$fromDate, $toDate])
    //         ->orderBy('created_at')
    //         ->get();

    //     $filteredIncentives = [];
    //     $processedDtrDates = []; // Use this to track processed DTR dates to avoid duplicates

    //     foreach ($allIncentives as $incentive) {
    //         // Parse the incentive's creation time and convert to local time
    //         $createdAtManila = Carbon::parse($incentive->created_at)->setTimezone('Asia/Manila');

    //         // Determine the "DTR Date" this incentive belongs to.
    //         // A DTR day starts at 6 AM. Any time before that belongs to the previous DTR date.
    //         $dtrDate = $createdAtManila->copy();
    //         if ($createdAtManila->hour < 6) {
    //             $dtrDate->subDay();
    //         }
    //         $dtrDateString = $dtrDate->format('Y-m-d');

    //         // If we have already processed this DTR date for the employee, skip to the next incentive.
    //         // This prevents creating duplicate entries if multiple incentives exist for the same DTR day.
    //         if (isset($processedDtrDates[$dtrDateString])) {
    //             continue;
    //         }

    //         // Mark this DTR date as processed
    //         $processedDtrDates[$dtrDateString] = true;

    //         // Define the full DTR time window: from 6 AM on the DTR date to 5:59:59 AM the next day.
    //         // All timestamps must be in UTC to match the database records.
    //         $dtrStart = Carbon::parse($dtrDateString . ' 06:00:00', 'Asia/Manila')->setTimezone('UTC');
    //         $dtrEnd = Carbon::parse($dtrDateString, 'Asia/Manila')->addDay()->setTime(5, 59, 59)->setTimezone('UTC');

    //         // Fetch all confirmed InitialBakerreports within this entire DTR window
    //         $bakerReports = InitialBakerreports::where('branch_id', $incentive->branch_id)
    //             ->where('status', 'confirmed')
    //             ->where('recipe_category', 'Dough')
    //             // Use the full DTR window for the query
    //             ->whereBetween('created_at', [$dtrStart, $dtrEnd])
    //             ->get();

    //         // Attach the total kilo from all reports found in the DTR cycle
    //         $incentive->baker_kilo_total = $bakerReports->sum('kilo');

    //         // Attach the full list of reports
    //         $incentive->baker_reports = $bakerReports;

    //         // Add the processed incentive to the output list
    //         $filteredIncentives[] = $incentive;
    //     }

    //     return response()->json($filteredIncentives);
    // }

    public function getIncentiveEmployeeReportsPerDtrCutOff($from, $to, $employee_id)
    {
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();

        // Fetch all incentive reports for the employee withoin the given range
        $allIncentives = IncentiveEmployeeReports::where('employee_id', $employee_id)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->with('branch')
            ->orderBy('created_at')
            ->get();

        $filteredIncentives = [];
        $processedDtrDates = [];

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
            $dtrStart = Carbon::parse("{$dtrDateString} 06:00:00", 'Asia/Manila')->setTimezone('UTC');
            $dtrEnd = Carbon::parse($dtrDateString, 'Asia/Manila')
                ->addDay()
                ->setTime(5, 59, 59)
                ->setTimezone('UTC');

            // Fetch confirmed Dough-category baker reports in that DTR window
            $bakerReports = InitialBakerreports::where('branch_id', $incentive->branch_id)
                ->where('status', 'confirmed')
                ->where('recipe_category', 'Dough')
                ->with('branchRecipe')
                ->whereBetween('created_at', [$dtrStart, $dtrEnd])
                ->get();

            // Attach computed and raw report data
            $incentive->baker_kilo_total = $bakerReports->sum('kilo');
            $incentive->baker_reports = $bakerReports;

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
