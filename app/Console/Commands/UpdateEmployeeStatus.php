<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\EmployeeOnLeave;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateEmployeeStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-employee-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        // Set employees ON-LEAVE
        $onLeaves = EmployeeOnLeave::where('status', 'Approved')
                    ->whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today)
                    ->get();

        foreach ($onLeaves as $leave) {
            Employee::where('id', $leave->employee_id)
                ->update(['status' => 'On-leave']);
        }

        // Set employees back to ACTIVE
        $endedLeaves = EmployeeOnLeave::where('status', 'Approved')
                ->whereDate('end_date', '<', $today)
                ->get();

        foreach ($endedLeaves as $leave) {
            Employee::where('id', $leave->employee_id)
                ->update(['status' => 'Active']);
        }

        return 0;
    }
}
