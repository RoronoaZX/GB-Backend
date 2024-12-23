<?php

namespace App\Http\Controllers;

use App\Models\DailyTimeRecord;
use App\Models\Device;
use App\Models\Employee;
use Illuminate\Http\Client\ResponseSequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;


class DailyTimeRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $dtr = DailyTimeRecord::with(['employee'])
            ->orderBy('created_at', 'desc')
            ->take(7)
            ->get()
            ->map(function ($record) {
                // Format the dates in Manila timezone
                $record->time_in = Carbon::parse($record->time_in)
                    ->timezone('Asia/Manila')
                    ->format('M. d, Y, g:i A'); // Example: "Sept. 14, 2024, 8:35 AM"

                $record->time_out = $record->time_out ? Carbon::parse($record->time_out)
                    ->timezone('Asia/Manila')
                    ->format('M. d, Y, g:i A') : null;

                $record->break_start = $record->break_start ? Carbon::parse($record->break_start)
                    ->timezone('Asia/Manila')
                    ->format('M. d, Y, g:i A') : null;

                $record->break_end = $record->break_end ? Carbon::parse($record->break_end)
                    ->timezone('Asia/Manila')
                    ->format('M. d, Y, g:i A') : null;

                $record->lunch_break_start = $record->lunch_break_start ? Carbon::parse($record->lunch_break_start)
                    ->timezone('Asia/Manila')
                    ->format('M. d, Y, g:i A') : null;

                $record->lunch_break_end = $record->lunch_break_end ? Carbon::parse($record->lunch_break_end)
                    ->timezone('Asia/Manila')
                    ->format('M. d, Y, g:i A') : null;

                return $record;
            });

        return response()->json($dtr);
    }

    public function searchDTR(Request $request)
    {
        $keyword = $request->input('keyword');

         $dtr = DailyTimeRecord::with('employee')
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

         return response()->json($dtr);
    }

    public function saveOvertime(Request $request)
    {
        $validatedData = $request->validate([
            'employee_id' => 'required|integer',
            'regularDateIN' => 'required|date',
            'overtime_in' => 'required|string', // Accept string format for parsing
            'overtime_out' => 'nullable|string', // Accept string format for parsing
        ]);

            // Extract data from the validated request
        $employeeId = $validatedData['employee_id'];
        $regularDateIN = $validatedData['regularDateIN'];
        $dateTimeIn = $validatedData['overtime_in'];
        $dateTimeOut = $validatedData['overtime_out'] ?? null;

          // Convert dateTimeIn to the database format (DATETIME)
        $dateTimeInFormatted = Carbon::createFromFormat('Y-m-d h:i A', $dateTimeIn)->format('Y-m-d H:i:s');

        // Convert dateTimeOut to the database format if provided
        $dateTimeOutFormatted = $dateTimeOut ? Carbon::createFromFormat('Y-m-d h:i A', $dateTimeOut)->format('Y-m-d H:i:s') : null;

          // Search for an existing record matching employee_id and regularDateIN (created_at column)
        $overtimeRecord = DailyTimeRecord::where('employee_id', $employeeId)
        ->whereDate('created_at', $regularDateIN)
        ->first();

        if ($overtimeRecord) {
            // Update the existing record with the new dateTimeIn and dateTimeOut
            $overtimeRecord->overtime_start = $dateTimeInFormatted;
            $overtimeRecord->overtime_end = $dateTimeOutFormatted;
            $overtimeRecord->save();
        } else {
            return response()->json([
                'message' => 'No matching overtime record found. Unable to create a new entry.',
            ], 404); // 404 Not Found HTTP status code
        }

        // Return a response
        return response()->json([
            'message' => 'Overtime data saved successfully.',
            'data' => $overtimeRecord ?? null,
        ], 200);
    }

    public function getDTRData(Request $request)
    {
        $employeeId = $request->input('employee_id');
        $startDate = $request->input('start_date', date('Y-m-10'));
        $endDate = $request->input('end_date', date('Y-m-25'));

        // Fetch the DTR data for the employee within the date range
        $dtrData = DailyTimeRecord::with(['employee.branch'])
            ->where('employee_id', $employeeId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($record) {
                // Format the dates in Manila timezone
                $record->time_in = Carbon::parse($record->time_in)
                    ->timezone('Asia/Manila')
                    ->format('M. d, Y, g:i A');

                $record->time_out = $record->time_out ? Carbon::parse($record->time_out)
                    ->timezone('Asia/Manila')
                    ->format('M. d, Y, g:i A') : null;

                $record->break_start = $record->break_start ? Carbon::parse($record->break_start)
                    ->timezone('Asia/Manila')
                    ->format('M. d, Y, g:i A') : null;

                $record->break_end = $record->break_end ? Carbon::parse($record->break_end)
                    ->timezone('Asia/Manila')
                    ->format('M. d, Y, g:i A') : null;

                $record->lunch_break_start = $record->lunch_break_start ? Carbon::parse($record->lunch_break_start)
                    ->timezone('Asia/Manila')
                    ->format('M. d, Y, g:i A') : null;

                $record->lunch_break_end = $record->lunch_break_end ? Carbon::parse($record->lunch_break_end)
                    ->timezone('Asia/Manila')
                    ->format('M. d, Y, g:i A') : null;

                return $record;
            });

        return response()->json($dtrData);
    }

    public function checkIdAndUuid(Request $request)
    {
        // Log received UUID and ID to debug
        \Log::info('Received UUID: ' . $request->uuid);
        \Log::info('Received ID: ' . $request->id);

        // Validate the incoming data
        $request->validate([
            'uuid' => 'required|string',
            'id' => 'required|string',
        ]);

        // Check if the device exists with the given UUID
        $device = Device::where('uuid', $request->uuid)->first();

        // Check if the user exists with the given ID
        $user = Employee::where('id', $request->id)->first();

        if ($device && $user) {
            // Both UUID and ID match
            return response()->json(['message' => 'OK']);
        } else {
            // Log which check failed
            if (!$device) {
                \Log::warning('No device found for UUID: ' . $request->uuid);
            }
            if (!$user) {
                \Log::warning('No user found for ID: ' . $request->id);
            }

            // Either UUID or ID is not valid
            return response()->json(['message' => 'Not Valid'], 400);
        }
    }

    public function checkDtrStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|string',
        ]);

        // Get the latest DTR entry for the employee
        $dtr = DailyTimeRecord::where('employee_id', $request->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($dtr) {
            // Check if the employee has timed in and not yet timed out
            if ($dtr->time_in && !$dtr->time_out) {
                return response()->json(['dtrStatus' => 'timed_in']);
            } else {
                return response()->json(['dtrStatus' => 'not_timed_in']);
            }
        }

        return response()->json(['dtrStatus' => 'not_timed_in']);
    }

    public function markTimeIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 422);
        }

        $lastDtr = DailyTimeRecord::where('employee_id', $request->employee_id)
            ->whereNull('time_out')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastDtr) {
            return response()->json(['message' => 'Already timed in, cannot time in again without timing out'], 400);
        }

        $dtr = new DailyTimeRecord();
        $dtr->employee_id = $request->employee_id;
        $dtr->time_in = now();
        $dtr->save();

        return response()->json([
            'message' => 'Time In marked successfully!',
            'dtrStatus' => 'time_in',
            'data' => $dtr
        ]);
    }

    public function markTimeOut(Request $request)
    {
        // Validate that the employee_id is provided in the request
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        // Find the most recent DTR record for the employee that doesn't have a time_out
        $dtr = DailyTimeRecord::where('employee_id', $request->employee_id)
                            ->whereNull('time_out') // Only records without time_out
                            ->latest('time_in') // Get the most recent one
                            ->first();

        // Check if a matching DTR record is found
        if (!$dtr) {
            return response()->json(['message' => 'No active time-in record found for this employee.'], 404);
        }

        // Mark the time_out
        $dtr->time_out = now();
        $dtr->save();

        return response()->json(['message' => 'Time Out marked successfully!', 'data' => $dtr]);
    }

    public function checkBreakStatus(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        $dtr = DailyTimeRecord::where('employee_id', $request->employee_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if(!$dtr) {
            return response()->json(['status' => 'no_dtr_found']);
        }

        if ($dtr->break_start && !$dtr->break_end) {
            return response()->json(['status' => 'on_break']);
        } elseif ($dtr->break_start && $dtr->break_end) {
            return response()->json(['status' => 'break_completed']);
        } else {
            return response()->json(['status' => 'no_break']);
        }
    }

    public function break(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id'
        ]);

        $dtr = DailyTimeRecord::where('employee_id', $request->employee_id)
            ->orderBy('created_at', 'desc')
            ->first();
        if(!$dtr) {
            return response()->json(['message' => 'No DTR record found for this employee.'], 404);
        }

        if ($dtr->break_start && !$dtr->break_end) {
            $dtr->break_end = now();
            $dtr->save();

            return response()->json(['message' => 'Break ended successfully!', 'data' => $dtr]);
        } else {
            $dtr->break_start = now();
            $dtr->break_end = null;
            $dtr->save();

            return response()->json(['message' => 'Break started successfully!', 'data' => $dtr]);
        }
    }

    public function checkLunchBreakStatus(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        $dtr = DailyTimeRecord::where('employee_id', $request->employee_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if(!$dtr) {
            return response()->json(['status' => 'no_dtr_found']);
        }

        if($dtr->lunch_break_start && !$dtr->lunch_break_end) {
            return response()->json(['status' => 'on_lunch_break']);
        } elseif ($dtr->lunch_break_start && $dtr->lunch_break_end) {
            return response()->json(['status' => 'lunch_break_completed']);
        } else {
            return response()->json(['status' => 'no_lunch_break']);
        }
    }

    public function lunchBreak(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        $dtr = DailyTimeRecord::where('employee_id', $request->employee_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if(!$dtr) {
            return response()->json(['message' => 'No DTR record found for this employee.'], 404);
        }

        if ($dtr->lunch_break_start && !$dtr->lunch_break_end) {
            $dtr->lunch_break_end = now();
            $dtr->save();

            return response()->json(['message' => 'Lunch Break ended successfully!', 'data' => $dtr]);
        } else {
            $dtr->lunch_break_start = now();
            $dtr->lunch_break_end = null;
            $dtr->save();

            return response()->json(['message' => 'Lunch Break started successfully!', 'data' => $dtr]);
        }
    }

    public function markBreak(Request $request, $id)
    {
        $dtr = DailyTimeRecord::find($id);

        if (!$dtr) {
            return response()->json(['message' => 'Record not found.'], 404);
        }

        $dtr->break_start = now();
        $dtr->save();

        return response()->json(['message' => 'Break started successfully!', 'data' => $dtr]);
    }

    public function endBreak(Request $request, $id)
    {
        $dtr = DailyTimeRecord::find($id);

        if(!$dtr) {
            return response()->json(['message' => 'Record not found.'], 404);
        }

        $dtr->break_end = now();
        $dtr->save();

        return response()->json(['message' => 'Break ended successfully!', 'data' => $dtr]);
    }

    public function getDtrRecord($id)
    {
        $dtr = DailyTimeRecord::with('emloyee')->find($id);

        if (!$dtr) {
            return response()->json(['message' => 'Record not found.'], 404);
        }
        return response()->json(['data' => $dtr]);
    }


}
