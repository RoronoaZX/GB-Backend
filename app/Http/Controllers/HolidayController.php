<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Request;
use App\Services\HistoryLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HolidayController extends Controller
{
    public function index(Request $request) // <-- Inject the Request object
    {
        // 1. Validate the incoming request to ensure year and month are provided
        $validated = $request->validate([
            'year'   => 'required|integer|date_format:Y',
            'month'  => 'required|integer|min:1|max:12',
        ]);

        // 2. Build the query using the validated year and month
        $holidays = Holiday::query()
                        ->whereYear('date', $validated['year'])   // Filter by year on the 'date' column
                        ->whereMonth('date', $validated['month'])  // Filter by month on the 'date' column
                        ->orderBy('date', 'asc')                   // Order by the actual date, which is more useful
                        ->get();

        // 3. Return the filtered results as JSON
        return response()->json($holidays);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Early check if holiday already exist for this date
        if ($this->checkIfHolidayExists($request->date)) {
            return response()->json([
                'message' => 'This date is already marked as a holiday'
            ], 409);
        }

        $validateData = $request->validate([
            'name'   => 'required|string|max:255',
            'date'   => 'required|date|unique:holidays,date',
            'type'   => 'required|string|max:191'
        ],[
            'date.unique' => 'This date is already marked as a holiday'
        ]);

        DB::beginTransaction();
        try {
            $holiday = Holiday::create($validateData);

            // LOG-29 — Holiday: Created
            HistoryLogService::log([
                'user_id'          => Auth::id(),
                'report_id'        => $holiday->id,
                'type_of_report'   => 'Holiday',
                'name'             => $holiday->name,
                'action'           => 'created',
                'updated_data'     => $holiday->toArray(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Holiday successfully created',
                'holiday' => $holiday,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create holiday', 'error' => $e->getMessage()], 500);
        }
    }

    private function checkIfHolidayExists($date)
    {
        return Holiday::where('date', $date)->exists();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $holiday = Holiday::find($id);

        if (!$holiday) {
            return response()->json([
                'message' => 'Device not found'
            ]);
        }

        DB::beginTransaction();
        try {
            $oldData = $holiday->toArray();
            $holiday->update($request->all());
            $updated_holiday = $holiday->fresh();

            // LOG-29 — Holiday: Updated
            HistoryLogService::log([
                'user_id'          => Auth::id(),
                'report_id'        => $holiday->id,
                'type_of_report'   => 'Holiday',
                'name'             => $holiday->name,
                'action'           => 'updated',
                'original_data'    => $oldData,
                'updated_data'     => $updated_holiday->toArray(),
            ]);

            DB::commit();

            return response()->json($updated_holiday);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update holiday', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $holiday = Holiday::find($id);

        if (!$holiday) {
            return response()->json([
                'message' => 'Holiday ID not found!'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $oldData = $holiday->toArray();
            $holiday->delete();

            // LOG-29 — Holiday: Deleted
            HistoryLogService::log([
                'user_id'          => Auth::id(),
                'report_id'        => $id,
                'type_of_report'   => 'Holiday',
                'name'             => $oldData['name'],
                'action'           => 'deleted',
                'original_data'    => $oldData,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Holiday deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete holiday', 'error' => $e->getMessage()], 500);
        }
    }
}
