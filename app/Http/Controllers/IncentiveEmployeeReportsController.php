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

    public function getIncentiveEmployeeReportsPerDtrCutOff($from, $to, $employee_id)
    {
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();

        // ✅ Only used here: Filter incentive reports by employee
        $incentives = IncentiveEmployeeReports::where('employee_id', $employee_id)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->orderBy('created_at') // Ensure earliest first
            ->get();

        $grouped = [];

        foreach ($incentives as $incentive) {
            $created = Carbon::parse($incentive->created_at)->timezone('Asia/Manila');
            $branchId = $incentive->branch_id;

            // ✅ Shift time logic (6AM–10PM for day; otherwise offshift)
            $dayStart = $created->copy()->setTime(6, 0, 0);
            $dayEnd = $created->copy()->setTime(22, 0, 0);
            $shift = $created->betweenIncluded($dayStart, $dayEnd) ? 'day' : 'offshift';

            // ✅ Effective date: same day if day shift, otherwise use previous day
            $effectiveDate = $shift === 'day'
                ? $created->toDateString()
                : $created->copy()->subDay()->toDateString();

            // ❌ Removed employee_id from the group key
            $groupKey = "{$branchId}_{$effectiveDate}_{$shift}";

            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'branch_id' => $branchId,
                    'employee_id' => $employee_id, // Still useful to show who this incentive is for
                    'date' => $effectiveDate,
                    'shift' => $shift,
                    'incentive' => $incentive,
                    'total_kilo' => 0,
                    'baker_reports' => [],
                    'debug' => [],
                ];
            }
        }

        // ✅ Get branch-wide baker reports, not per employee
        foreach ($grouped as &$group) {
            $date = $group['date'];

            // ✅ Default shift window: 6AM–10PM
            $start = Carbon::parse($date)->timezone('Asia/Manila')->setTime(6, 0, 0);
            $end = Carbon::parse($date)->timezone('Asia/Manila')->setTime(22, 0, 0);

            // ✅ Adjust for offshift: 10:01 PM – 5:59 AM next day
            if ($group['shift'] === 'offshift') {
                $start = Carbon::parse($date)->timezone('Asia/Manila')->setTime(22, 1, 0);
                $end = Carbon::parse($date)->copy()->addDay()->timezone('Asia/Manila')->setTime(5, 59, 59);
            }

            $bakerReports = InitialBakerreports::where('branch_id', $group['branch_id'])
                ->where('status', 'confirmed')
                ->whereBetween('created_at', [$start, $end])
                ->get();

            foreach ($bakerReports as $report) {
                Log::info('Matched Baker Report', [
                    'baker_report_id' => $report->id,
                    'created_at' => $report->created_at,
                    'start_window' => $start->toDateTimeString(),
                    'end_window' => $end->toDateTimeString(),
                ]);
            }

            $group['baker_reports'] = $bakerReports;
            $group['total_kilo'] = $bakerReports->sum('kilo');
            $group['debug'] = [
                'start_window' => $start->toDateTimeString(),
                'end_window' => $end->toDateTimeString(),
                'matched_count' => $bakerReports->count()
            ];
        }

        return response()->json(array_values($grouped));
    }


    // public function getIncentiveEmployeeReportsPerDtrCutOff($from, $to, $employee_id)
    // {
    //     $fromDate = Carbon::parse($from)->startOfDay();
    //     $toDate = Carbon::parse($to)->endOfDay();

    //     // Fetch all incentive reports for the employee
    //     $incentives = IncentiveEmployeeReports::where('employee_id', $employee_id)
    //         ->whereBetween('created_at', [$fromDate, $toDate])
    //         ->orderBy('created_at') // Ensure earliest first
    //         ->get();

    //     $grouped = [];

    //     foreach ($incentives as $incentive) {
    //         $created = Carbon::parse($incentive->created_at)->timezone('Asia/Manila');
    //         $branchId = $incentive->branch_id;

    //         // Determine shift
    //         $dayStart = $created->copy()->setTime(6, 0, 0);
    //         $dayEnd = $created->copy()->setTime(22, 0, 0);
    //         $shift = $created->betweenIncluded($dayStart, $dayEnd) ? 'day' : 'offshift';

    //         // Use the shift's effective date
    //         $effectiveDate = $shift === 'day'
    //             ? $created->toDateString()
    //             : $created->copy()->subDay()->toDateString();

    //         $groupKey = "{$branchId}_{$employee_id}_{$effectiveDate}_{$shift}";

    //         // Only keep the first report for each shift
    //         if (!isset($grouped[$groupKey])) {
    //             $grouped[$groupKey] = [
    //                 'branch_id' => $branchId,
    //                 'employee_id' => $employee_id,
    //                 'date' => $effectiveDate,
    //                 'shift' => $shift,
    //                 'incentive' => $incentive,
    //                 'total_kilo' => 0,
    //                 'baker_reports' => [],
    //                 'debug' => [], // For diagnostics
    //             ];
    //         }
    //     }

    //     // Fetch related baker reports per shift (no employee_id filtering)
    //     foreach ($grouped as &$group) {
    //         $date = $group['date'];
    //         $start = Carbon::parse($date)->setTimezone('Asia/Manila')->setTime(6, 0, 0);
    //         $end = Carbon::parse($date)->setTimezone('Asia/Manila')->setTime(22, 0, 0);

    //         if ($group['shift'] === 'offshift') {
    //             // Shift window is 10:01 PM of current date to 5:59 AM of next date
    //             $start = Carbon::parse($date)->setTimezone('Asia/Manila')->setTime(22, 1, 0);
    //             $end = Carbon::parse($date)->copy()->addDay()->setTimezone('Asia/Manila')->setTime(5, 59, 59);
    //         }

    //         $bakerReports = InitialBakerreports::where('branch_id', $group['branch_id'])
    //             ->where('status', 'confirmed')
    //             ->whereBetween('created_at', [$start, $end])
    //             ->get();

    //         // Debug log for diagnostics
    //         foreach ($bakerReports as $report) {
    //             Log::info('Matched Baker Report', [
    //                 'baker_report_id' => $report->id,
    //                 'created_at' => $report->created_at,
    //                 'start_window' => $start->toDateTimeString(),
    //                 'end_window' => $end->toDateTimeString(),
    //             ]);
    //         }

    //         // Add data to group
    //         $group['baker_reports'] = $bakerReports;
    //         $group['total_kilo'] = $bakerReports->sum('kilo');
    //         $group['debug'] = [
    //             'start_window' => $start->toDateTimeString(),
    //             'end_window' => $end->toDateTimeString(),
    //             'matched_count' => $bakerReports->count()
    //         ];
    //     }

    //     return response()->json(array_values($grouped));
    // }



    // public function getIncentiveEmployeeReportsPerDtrCutOff($from, $to, $employee_id)
    // {
    //     $fromDate = Carbon::parse($from)->startOfDay();
    //     $toDate = Carbon::parse($to)->endOfDay();

    //     $incentives = IncentiveEmployeeReports::where('employee_id', $employee_id)
    //         ->whereBetween('created_at', [$fromDate, $toDate])
    //         ->orderBy('created_at')
    //         ->get();

    //     $grouped = [];

    //     foreach ($incentives as $incentive) {
            // $created = Carbon::parse($incentive->created_at)->setTimezone('Asia/Manila');
    //         $branchId = $incentive->branch_id;

    //         // Determine shift
            // $dayShiftStart = $created->copy()->setTime(6, 0, 0);
            // $dayShiftEnd = $created->copy()->setTime(22, 0, 0);
            // $isDayShift = $created->between($dayShiftStart, $dayShiftEnd);
            // $shift = $isDayShift ? 'day' : 'offshift';

    //         // Determine effective date for grouping
    //         $effectiveDate = $isDayShift
    //             ? $created->toDateString()
    //             : $created->copy()->subDay()->toDateString();

    //         $groupKey = "{$branchId}_{$employee_id}_{$effectiveDate}_{$shift}";

    //         if (!isset($grouped[$groupKey])) {
    //             $grouped[$groupKey] = [
    //                 'branch_id' => $branchId,
    //                 'employee_id' => $employee_id,
    //                 'date' => $effectiveDate,
    //                 'shift' => $shift,
    //                 'incentive' => $incentive,
    //                 'total_kilo' => 0,
    //                 'baker_reports' => [],
    //             ];
    //         }
    //     }

    //     // Now fetch relevant InitialBakerreports for each shift group
    //     foreach ($grouped as &$group) {
    //         if ($group['shift'] === 'day') {
    //             $start = Carbon::parse($group['date'])->setTime(6, 0, 0);
    //             $end = Carbon::parse($group['date'])->setTime(22, 0, 0);
    //         } else {
    //             $start = Carbon::parse($group['date'])->setTime(22, 1, 0);
    //             $end = Carbon::parse($group['date'])->copy()->addDay()->setTime(5, 59, 59);
    //         }

    //         $bakerReports = InitialBakerreports::where('branch_id', $group['branch_id'])
    //             ->where('status', 'confirmed')
    //             ->whereBetween('created_at', [$start, $end])
    //             ->get();

    //         $group['baker_reports'] = $bakerReports;
    //         $group['total_kilo'] = $bakerReports->sum('kilo');
    //     }

    //     return response()->json(array_values($grouped));
    // }



    //  public function getIncentiveEmployeeReportsPerDtrCutOff($from, $to, $employee_id): JsonResponse
//     {
//         $fromDate = Carbon::parse($from)->startOfDay();
//         $toDate = Carbon::parse($to)->endOfDay();

//         // 1. Fetch all incentives for the employee within the date range
//         $allIncentives = IncentiveEmployeeReports::where('employee_id', $employee_id)
//             ->whereBetween('created_at', [$fromDate, $toDate])
//             ->get();

//         // 2. Filter to get only the incentives created during the graveyard shift.
//         // This is much cleaner than a complex 'between' check across days.
//         $graveyardShiftIncentives = $allIncentives->filter(function ($incentive) {
//             $time = Carbon::parse($incentive->created_at)->format('H:i:s');

//             // The time is either after 10:01 PM OR before 5:59:59 AM
//             return $time >= '22:01:00' || $time <= '05:59:59';
//         });

//         // 3. For each graveyard shift incentive, aggregate the baker report data
//         $reportData = $graveyardShiftIncentives->map(function ($incentive) {
//             $incentiveTimestamp = Carbon::parse($incentive->created_at);

//             // 4. Determine the correct "Work Day" for reporting purposes
//             // If the time is after 10 PM, the work day is today.
//             // If the time is before 6 AM, it belongs to the *previous* day's shift.
//             $reportingDate = $incentiveTimestamp->hour >= 22
//                 ? $incentiveTimestamp->copy()
//                 : $incentiveTimestamp->copy()->subDay();

//             // 5. Define the full day for the bakery data query
//             $startOfReportingDay = $reportingDate->copy()->startOfDay();
//             $endOfReportingDay = $reportingDate->copy()->endOfDay();

//             // Find and sum all baker reports for the same branch on that entire "Work Day"
//             $totalKilo = InitialBakerreports::where('branch_id', $incentive->branch_id)
//                 ->whereBetween('created_at', [$startOfReportingDay, $endOfReportingDay])
//                 ->sum('kilo');

//             // 6. Attach the aggregated sum to the incentive object
//             $incentive->total_kilo_for_shift_day = $totalKilo;

//             return $incentive;
//         });

//         // Return the final collection as a clean JSON array
//         return response()->json($reportData->values());
//     }


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
