<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\HistoryLog;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class HistoryLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        $historyLogs = HistoryLog::with('userId.employee')
            ->orderBy('created_at', 'desc') // or ->latest()
            ->get()
            ->map(function($historyLog) {
                $historyLog->designation = $historyLog->designation_type === 'branch'
                    ? Branch::find($historyLog->designation)
                    : Warehouse::find($historyLog->designation);

                return $historyLog;
            });

        return response()->json($historyLogs);
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
    public function show(HistoryLog $historyLog)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HistoryLog $historyLog)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HistoryLog $historyLog)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HistoryLog $historyLog)
    {
        //
    }
}
