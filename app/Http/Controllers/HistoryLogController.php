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

    public function index(Request $request)
    {
        $query = HistoryLog::with('user.employee');

        // Global Search Filter Engine
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('type_of_report', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('updated_field', 'like', "%{$search}%")
                  ->orWhereHas('user.employee', function($qStaff) use ($search) {
                      $qStaff->where('firstname', 'like', "%{$search}%")
                             ->orWhere('lastname', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('branch_id')) {
            $query->where('designation', $request->branch_id)
                  ->where('designation_type', 'branch');
        }

        if ($request->has('warehouse_id')) {
            $query->where('designation', $request->warehouse_id)
                  ->where('designation_type', 'warehouse');
        }

        $perPage = $request->query('per_page', 15);

        // Execute Paginated API Fetch strictly
        $historyLogs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Transform only the chunked 15 records to resolve massive N+1 lag
        $historyLogs->getCollection()->transform(function($historyLog) {
            $historyLog->designation = $historyLog->designation_type === 'branch'
                ? Branch::find($historyLog->designation)
                : Warehouse::find($historyLog->designation);

            return $historyLog;
        });

        return response()->json($historyLogs);
    }
}
