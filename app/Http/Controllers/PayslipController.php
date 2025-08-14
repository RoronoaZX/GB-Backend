<?php

namespace App\Http\Controllers;

use App\Models\Payslip;
use App\Models\PayslipBakerReport;
use App\Models\PayslipDtr;
use App\Models\PayslipDtrHolidays;
use App\Models\PayslipDtrRecord;
use App\Models\PayslipEarnings;
use App\Models\PayslipIncentive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PayslipController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1️⃣ Validate the request before doing anything
        $validator = Validator::make($request->all(), [
            'employee_id'            => 'required|integer|exists:employees,id',
            'from'                   => 'required|string',
            'to'                     => 'required|string',
            'payroll_release_date'   => 'required|string',
            'rate_per_day'           => 'required|numeric|min:0',
            'total_days'             => 'required|integer|min:0',
            'uniform_balance'        => 'required|numeric|min:0',
            'credit_balance'         => 'required|numeric|min:0',
            'cash_advance_balance'   => 'required|numeric|min:0',
            'total_earnings'         => 'required|numeric|min:0',
            'total_deductions'       => 'required|numeric|min:0',
            'net_income'             => 'required|numeric|min:0',

            // Nested object validation
            'payslip_earnings.working_hours_pay'     => 'required|numeric|min:0',
            'payslip_earnings.overtime_pay'          => 'required|numeric|min:0',
            'payslip_earnings.night_diff_pay'        => 'required|numeric|min:0',
            'payslip_earnings.holidays_pay'          => 'required|numeric|min:0',
            'payslip_earnings.allowances_pay'        => 'required|numeric|min:0',
            'payslip_earnings.incentives_pay'        => 'required|numeric|min:0',
            'payslip_earnings.undertime_pay'         => 'required|numeric|min:0',
            'payslip_earnings.payslip_incentives'    => 'nullable|array',

            // Incentives validation
            'payslip_earnings.payslip_incentives.*.branch_id'            => 'required|integer|exists:branches,id',
            'payslip_earnings.payslip_incentives.*.employee_id'          => 'required|integer|exists:employees,id',
            'payslip_earnings.payslip_incentives.*.designation'          => 'required|string|max:255',
            'payslip_earnings.payslip_incentives.*.baker_kilo_total'     => 'required|numeric|min:0',
            'payslip_earnings.payslip_incentives.*.incentive_value'      => 'required|numeric|min:0',
            'payslip_earnings.payslip_incentives.*.multiplier_used'      => 'required|numeric|min:0',
            'payslip_earnings.payslip_incentives.*.number_of_employees'  => 'required|numeric|min:0',
            'payslip_earnings.payslip_incentives.*.shift_status'         => 'required|string|max:255',
            'payslip_earnings.payslip_incentives.*.target'               => 'required|numeric|min:0',
            'payslip_earnings.payslip_incentives.*.baker_reports'        => 'nullable|array',

            // Baker reports inside incentives
            'payslip_earnings.payslip_incentives.*.baker_reports.*.id'               => 'required|integer|exists:initial_bakerreports,id',
            'payslip_earnings.payslip_incentives.*.baker_reports.*.branch_id'        => 'required|integer|exists:branches,id',
            'payslip_earnings.payslip_incentives.*.baker_reports.*.branch_recipe_id' => 'required|integer|exists:branch_recipes,id',
            'payslip_earnings.payslip_incentives.*.baker_reports.*.user_id'          => 'required|integer|exists:users,id',
            'payslip_earnings.payslip_incentives.*.baker_reports.*.kilo'             => 'required|numeric|min:0',
            'payslip_earnings.payslip_incentives.*.baker_reports.*.actual_target'    => 'required|numeric|min:0',
            'payslip_earnings.payslip_incentives.*.baker_reports.*.over'             => 'required|numeric|min:0',
            'payslip_earnings.payslip_incentives.*.baker_reports.*.short'            => 'required|numeric|min:0',
            'payslip_earnings.payslip_incentives.*.baker_reports.*.recipe_category'  => 'required|string|max:255',
            'payslip_earnings.payslip_incentives.*.baker_reports.*.target'           => 'required|string|max:255',
            'payslip_earnings.payslip_incentives.*.baker_reports.*.status'           => 'required|string|max:255',
            'payslip_earnings.payslip_incentives.*.baker_reports.*.remark'           => 'nullable|string|max:255',
            'payslip_earnings.payslip_incentives.*.baker_reports.*.created_at'       => 'required|date',

            // Payslip DTR
            'payslip_dtr.end'                    => 'required|string',
            'payslip_dtr.from'                   => 'required|string',
            'payslip_dtr.release_date'           => 'required|string',
            'payslip_dtr.holidays'               => 'required|array',
            'payslip_dtr.payslip_dtr_record'    => 'required|array',

            // Payslip DTR Holidays
            'payslip_dtr.holidays.*.date'        => 'required|string',
            'payslip_dtr.holidays.*.name'        => 'required|string',
            'payslip_dtr.holidays.*.type'        => 'required|string',

            // Payslip DTR Records
            'payslip_dtr.payslipr_dtr_record.*.id'                       => 'required|integer|exists:daily_time_records,id',
            'payslip_dtr.payslipr_dtr_record.*.device_uuid_in'           => 'required|string',
            'payslip_dtr.payslipr_dtr_record.*.device_uuid_out'          => 'required|string',
            'payslip_dtr.payslipr_dtr_record.*.employee_id'              => 'required|integer|exists:employees,id',
            'payslip_dtr.payslipr_dtr_record.*.employee_allowance'       => 'required|numeric|min:0',
            'payslip_dtr.payslipr_dtr_record.*.time_in'                  => 'required|string',
            'payslip_dtr.payslipr_dtr_record.*.time_out'                 => 'required|string',
            'payslip_dtr.payslipr_dtr_record.*.lunch_break_start'        => 'required|string',
            'payslip_dtr.payslipr_dtr_record.*.lunch_break_end'          => 'required|string',
            'payslip_dtr.payslipr_dtr_record.*.break_start'              => 'required|string',
            'payslip_dtr.payslipr_dtr_record.*.break_end'                => 'required|string',
            'payslip_dtr.payslipr_dtr_record.*.overtime_start'           => 'required|string',
            'payslip_dtr.payslipr_dtr_record.*.overtime_end'             => 'required|string',
            'payslip_dtr.payslipr_dtr_record.*.overtime_reason'          => 'required|string',
            'payslip_dtr.payslipr_dtr_record.*.ot_status'                => 'required|string',
            'payslip_dtr.payslipr_dtr_record.*.approved_by'              => 'required|integer|exists:users,id',
            'payslip_dtr.payslipr_dtr_record.*.declined_reason'          => 'nullable|string',
            'payslip_dtr.payslipr_dtr_record.*.half_day_reason'          => 'nullable|string',
            'payslip_dtr.payslipr_dtr_record.*.shift_status'             => 'required|string',
            'payslip_dtr.payslipr_dtr_record.*.schedule_in'              => 'required|string',
            'payslip_dtr.payslipr_dtr_record.*.schedule_out'             => 'required|string',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2️⃣ Run all DB writes in a single transaction
        DB::transaction(function () use ($request) {

            // Payslip
            $payslip = Payslip::create([
                'employee_id'            => $request->employee_id,
                'from'                   => $request->from,
                'to'                     => $request->to,
                'payroll_release_date'   => $request->payroll_release_date,
                'rate_per_day'           => $request->rate_per_day,
                'total_days'             => $request->total_days,
                'cash_advance_balance'   => $request->cash_advance_balance,
                'credit_balance'         => $request->credit_balance,
                'uniform_balance'        => $request->uniform_balance,
                'total_earnings'         => $request->total_earnings,
                'total_deductions'       => $request->total_deductions,
                'net_income'             => $request->net_income
            ]);

            // Earnings summary
            $earningSummary = PayslipEarnings::create([
                'payslip_id'            => $payslip->id,
                'working_hours_pay'     => $request->payslip_earnings['working_hours_pay'],
                'overtime_pay'          => $request->payslip_earnings['overtime_pay'],
                'night_diff_pay'        => $request->payslip_earnings['night_diff_pay'],
                'holidays_pay'          => $request->payslip_earnings['holidays_pay'],
                'allowances_pay'        => $request->payslip_earnings['allowances_pay'],
                'incentives_pay'        => $request->payslip_earnings['incentives_pay'],
                'undertime_pay'         => $request->payslip_earnings['undertime_pay']
            ]);

            // Incentives
            $payslipIncentives = $request->input('payslip_earnings.payslip_incentives', []);

            foreach ($payslipIncentives as $incentive) {
                $payslipIncentive = PayslipIncentive::create([
                    'payslip_earning_id'     => $earningSummary->id,
                    'branch_id'              => $incentive['branch_id'],
                    'employee_id'            => $incentive['employee_id'],
                    'designation'            => $incentive['designation'],
                    'baker_kilo_total'       => $incentive['baker_kilo_total'],
                    'excess_kilo'            => $incentive['excess_kilo'],
                    'incentive_value'        => $incentive['incentive_value'],
                    'multiplier_used'        => $incentive['multiplier_used'],
                    'number_of_employees'    => $incentive['number_of_employees'],
                    'shift_status'           => $incentive['shift_status'],
                    'target'                 => $incentive['target']
                ]);

                // Handle baker reports only if they exist and are an array
                $bakerReports = $incentive['baker_reports'] ?? [];
                if (is_array($bakerReports) && !empty($bakerReports)) {
                    foreach ($incentive['baker_reports'] as $report) {
                        PayslipBakerReport::create([
                            'payslip_incentive_id'       => $payslipIncentive->id,
                            'branch_id'                  => $report['branch_id'],
                            'user_id'                    => $report['user_id'],
                            'initial_bakerreport_id'     => $report['id'],
                            'branch_recipe_id'           => $report['branch_recipe_id'],
                            'recipe_category'            => $report['recipe_category'],
                            'status'                     => $report['status'],
                            'kilo'                       => $report['kilo'],
                            'short'                      => $report['short'],
                            'over'                       => $report['over'],
                            'actual_target'              => $report['actual_target'],
                            'target'                     => $report['target'],
                            'created_at'                 => $report['created_at']
                        ]);
                    }
                }
            }

            // DTR
            $dtr = PayslipDtr::create([
                'payslip_id'        => $payslip->id,
                'from'              => $request->payslip_dtr['from'],
                'to'                => $request->payslip_dtr['end'],
                'release_date'      => $request->payslip_dtr['release_date'],
            ]);

            // DTR Holidays
            $dtrHolidays = $request->input('payslip_dtr.holidays', []);
            foreach ($dtrHolidays as $holidays) {
                PayslipDtrHolidays::create([
                    'payslip_dtr_id' => $dtr->id,
                    'date'           => $holidays['date'],
                    'name'           => $holidays['name'],
                    'type'           => $holidays['type'],
                ]);
            }

            // DTR Records
            $dtrRecords = $request->input('payslip_dtr.payslipr_dtr_record', []);
            foreach ($dtrRecords as $record) {
                PayslipDtrRecord::create([
                    'payslip_dtr_id'        => $dtr->id,
                    'dtr_id'                => $record['id'],
                    'device_uuid_in'        => $record['device_uuid_in'],
                    'device_uuid_out'       => $record['device_uuid_out'],
                    'employee_id'           => $record['employee_id'],
                    'employee_allowance'    => $record['employee_allowance'],
                    'time_in'               => $record['time_in'],
                    'time_out'              => $record['time_out'],
                    'lunch_break_start'     => $record['lunch_break_start'],
                    'lunch_break_end'       => $record['lunch_break_end'],
                    'break_start'           => $record['break_start'],
                    'break_end'             => $record['break_end'],
                    'overtime_start'        => $record['overtime_start'],
                    'overtime_end'          => $record['overtime_end'],
                    'overtime_reason'       => $record['overtime_reason'],
                    'ot_status'             => $record['ot_status'],
                    'approved_by'           => $record['approved_by'],
                    'declined_reason'       => $record['declined_reason'],
                    'half_day_reason'       => $record['half_day_reason'],
                    'shift_status'          => $record['shift_status'],
                    'schedule_in'           => $record['schedule_in'],
                    'schedule_out'          => $record['schedule_out'],
                ]);
            }

        });

        // 3️⃣ Success response
        return response()->json([
            'message' => 'Payslip saved successfully'
        ]);
    }



    /**
     * Display the specified resource.
     */
    public function show(Payslip $payslip)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payslip $payslip)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payslip $payslip)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payslip $payslip)
    {
        //
    }
}
