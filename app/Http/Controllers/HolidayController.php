<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Request;

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

        $holiday = Holiday::create($validateData);

        return response()->json([
            'message' => 'Device successfully created',
            'holiday' => $holiday,
        ], 201);
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

        $holiday->update($request->all());

        $updated_holiday = $holiday->fresh();

        return response()->json($updated_holiday);
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

        $holiday->delete();

        return response()->json([
            'message' => 'Holiday deleted successfully'
        ]);
    }
}
