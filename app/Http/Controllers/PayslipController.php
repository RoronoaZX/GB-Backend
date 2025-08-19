<?php

namespace App\Http\Controllers;

use App\Models\CashAdvance;
use App\Models\EmployeeCredits;
use App\Models\Payslip;
use App\Models\PayslipBakerReport;
use App\Models\PayslipDeductionBenefits;
use App\Models\PayslipDeductionCa;
use App\Models\PayslipDeductionCharges;
use App\Models\PayslipDeductionCredit;
use App\Models\PayslipDeductions;
use App\Models\PayslipDeductionUniformPants;
use App\Models\PayslipDeductionUniforms;
use App\Models\PayslipDeductionUniformTshirt;
use App\Models\PayslipDtr;
use App\Models\PayslipDtrHolidays;
use App\Models\PayslipDtrRecord;
use App\Models\PayslipEarnings;
use App\Models\PayslipHolidaySummary;
use App\Models\PayslipIncentive;
use App\Models\Uniform;
use App\Models\UniformTshirt;
use Carbon\Carbon;
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
    // public function store(StorePayslipRequest $request)
    // {
    //     // Check
    // }
    public function store(Request $request)
    {
        // 1️⃣ Validate the request before doing anything
        $validator = Validator::make($request->all(), [
            'employee_id'               => 'required|integer|exists:employees,id',
            'from'                      => 'required|string',
            'to'                        => 'required|string',
            'payroll_release_date'      => 'required|string',
            'rate_per_day'              => 'required|numeric|min:0',
            'total_days'                => 'required|integer|min:0',
            'uniform_balance'           => 'required|numeric|min:0',
            'credit_balance'            => 'required|numeric|min:0',
            'cash_advance_balance'      => 'required|numeric|min:0',
            'total_earnings'            => 'required|numeric|min:0',
            'total_deductions'          => 'required|numeric|min:0',
            'net_income'                => 'required|numeric|min:0',
            'payslip_earnings'          => 'nullable|array',
            'payslip_dtr'               => 'nullable|array',
            'payslip_holiday_summary'   => 'nullable|array',
            'payslip_deductions'       => 'nullable|array',

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
            'payslip_dtr.holidays'               => 'nullable|array',
            'payslip_dtr.payslip_dtr_record'     => 'required|array',

            // Payslip DTR Holidays
            'payslip_dtr.holidays.*.date'        => 'required|string',
            'payslip_dtr.holidays.*.name'        => 'required|string',
            'payslip_dtr.holidays.*.type'        => 'required|string',

            // Payslip DTR Records
            'payslip_dtr.payslip_dtr_record.*.id'                       => 'required|integer|exists:daily_time_records,id',
            'payslip_dtr.payslip_dtr_record.*.device_uuid_in'           => 'required|string',
            'payslip_dtr.payslip_dtr_record.*.device_uuid_out'          => 'required|string',
            'payslip_dtr.payslip_dtr_record.*.employee_id'              => 'required|integer|exists:employees,id',
            'payslip_dtr.payslip_dtr_record.*.employee_allowance'       => 'nullable|numeric|min:0',
            'payslip_dtr.payslip_dtr_record.*.time_in'                  => 'required|string',
            'payslip_dtr.payslip_dtr_record.*.time_out'                 => 'required|string',
            'payslip_dtr.payslip_dtr_record.*.lunch_break_start'        => 'nullable|string',
            'payslip_dtr.payslip_dtr_record.*.lunch_break_end'          => 'nullable|string',
            'payslip_dtr.payslip_dtr_record.*.break_start'              => 'nullable|string',
            'payslip_dtr.payslip_dtr_record.*.break_end'                => 'nullable|string',
            'payslip_dtr.payslip_dtr_record.*.overtime_start'           => 'nullable|string',
            'payslip_dtr.payslip_dtr_record.*.overtime_end'             => 'nullable|string',
            'payslip_dtr.payslip_dtr_record.*.overtime_reason'          => 'nullable|string',
            'payslip_dtr.payslip_dtr_record.*.ot_status'                => 'nullable|string',
            'payslip_dtr.payslip_dtr_record.*.approved_by'              => 'nullable|integer',
            'payslip_dtr.payslip_dtr_record.*.declined_reason'          => 'nullable|string',
            'payslip_dtr.payslip_dtr_record.*.half_day_reason'          => 'nullable|string',
            'payslip_dtr.payslip_dtr_record.*.shift_status'             => 'nullable|string',
            'payslip_dtr.payslip_dtr_record.*.schedule_in'              => 'nullable|string',
            'payslip_dtr.payslip_dtr_record.*.schedule_out'             => 'nullable|string',

            // Payslip Holiday Summary
            'payslip_holiday_summary.dtr_summary'                    => 'required|array',
            'payslip_holiday_summary.dtr_summary.*.id'               => 'required|string',
            'payslip_holiday_summary.dtr_summary.*.additionalPay'    => 'required|numeric|min:0',
            'payslip_holiday_summary.dtr_summary.*.date'             => 'required|string',
            'payslip_holiday_summary.dtr_summary.*.holidayRateText'  => 'required|string',
            'payslip_holiday_summary.dtr_summary.*.holidayType'      => 'required|string',
            'payslip_holiday_summary.dtr_summary.*.type'             => 'required|string',
            'payslip_holiday_summary.dtr_summary.*.workedHours'      => 'required|numeric|min:0',

            // Payslip Deductions
            'payslip_deductions.benefits_total'                 => 'nullable|numeric|min:0',
            'payslip_deductions.cash_advance_total'             => 'nullable|numeric|min:0',
            'payslip_deductions.credit_total'                   => 'nullable|numeric|min:0',
            'payslip_deductions.employee_charge_total'          => 'nullable|numeric|min:0',
            'payslip_deductions.total_deductions'               => 'nullable|numeric|min:0',
            'payslip_deductions.uniform_total'                  => 'nullable|numeric|min:0',
            'payslip_deductions.payslip_deduction_benefits'     => 'nullable',
            'payslip_deductions.payslip_deduction_ca'           => 'nullable|array',
            'payslip_deductions.payslip_deduction_charges'      => 'nullable|array',
            'payslip_deductions.payslip_deduction_credits'      => 'nullable|array',
            'payslip_deductions.payslip_deduction_uniforms'     => 'nullable',

            // Payslip Deduction Benefits
            'payslip_deductions.payslip_deduction_benefits.hdmf'    => 'nullable|numeric|min:0',
            'payslip_deductions.payslip_deduction_benefits.phic'    => 'nullable|numeric|min:0',
            'payslip_deductions.payslip_deduction_benefits.sss'     => 'nullable|numeric|min:0',

            // Payslip Deduction CA
            'payslip_deductions.payslip_deduction_ca.*.id'                    => 'required|integer|exists:cash_advances,id',
            'payslip_deductions.payslip_deduction_ca.*.created_at'            => 'required|date',
            'payslip_deductions.payslip_deduction_ca.*.employee_id'           => 'required|integer|exists:employees,id',
            'payslip_deductions.payslip_deduction_ca.*.number_of_payments'    => 'required|integer|min:0',
            'payslip_deductions.payslip_deduction_ca.*.payment_per_payroll'   => 'required|numeric|min:0',
            'payslip_deductions.payslip_deduction_ca.*.reason'                => 'required|string',
            'payslip_deductions.payslip_deduction_ca.*.remaining_payments'    => 'required|numeric|min:0',

            // Payslip Deduction Charges
            'payslip_deductions.payslip_deduction_charges.*.id'                    => 'required|integer|exists:sales_reports,id',
            'payslip_deductions.payslip_deduction_charges.*.user_id'               => 'required|integer|exists:users,id',
            'payslip_deductions.payslip_deduction_charges.*.created_at'            => 'required|date',
            'payslip_deductions.payslip_deduction_charges.*.branch_id'             => 'required|integer|exists:branches,id',
            'payslip_deductions.payslip_deduction_charges.*.charges_amount'        => 'required|numeric|min:0',

            // Payslip Deduction Uniforms
            'payslip_deductions.payslip_deduction_uniforms.id'                   => 'nullable|integer',
            'payslip_deductions.payslip_deduction_uniforms.date'                 => 'nullable|date',
            'payslip_deductions.payslip_deduction_uniforms.employee_id'          => 'nullable|integer',
            'payslip_deductions.payslip_deduction_uniforms.numberOfPayments'     => 'nullable|integer|min:0',
            'payslip_deductions.payslip_deduction_uniforms.paymentsPerPayroll'   => 'nullable|numeric|min:0',
            'payslip_deductions.payslip_deduction_uniforms.reason'               => 'nullable|string',
            'payslip_deductions.payslip_deduction_uniforms.remainingPayments'    => 'nullable|numeric|min:0',
            'payslip_deductions.payslip_deduction_uniforms.pants'                => 'nullable|array',
            'payslip_deductions.payslip_deduction_uniforms.t_shirt'             => 'nullable|array',

            // Payslip Deduction Uniform Pants
            'payslip_deductions.payslip_deduction_uniforms.pants.*.id'             => 'nullable|integer|exists:uniform_pants,id',
            'payslip_deductions.payslip_deduction_uniforms.pants.*.created_at'     => 'nullable|date',
            'payslip_deductions.payslip_deduction_uniforms.pants.*.pcs'            => 'nullable|integer|min:0',
            'payslip_deductions.payslip_deduction_uniforms.pants.*.price'          => 'nullable|numeric|min:0',
            'payslip_deductions.payslip_deduction_uniforms.pants.*.size'           => 'nullable|string',

            // Payslip Deduction Uniform T-Shirts
            'payslip_deductions.payslip_deduction_uniforms.t_shirts.*.id'             => 'nullable|integer|exists:uniform_tshirts,id',
            'payslip_deductions.payslip_deduction_uniforms.t_shirts.*.created_at'     => 'nullable|date',
            'payslip_deductions.payslip_deduction_uniforms.t_shirts.*.pcs'            => 'nullable|integer|min:0',
            'payslip_deductions.payslip_deduction_uniforms.t_shirts.*.price'          => 'nullable|numeric|min:0',
            'payslip_deductions.payslip_deduction_uniforms.t_shirts.*.size'           => 'nullable|string',

            // Payslip Deduction Credit
            'payslip_deductions.payslip_deduction_credits.*.id'                    => 'nullable|integer|exists:employee_credit_products,id',
            'payslip_deductions.payslip_deduction_credits.*.branch_id'             => 'nullable|integer|exists:branches,id',
            'payslip_deductions.payslip_deduction_credits.*.sales_report_id'       => 'nullable|integer',
            'payslip_deductions.payslip_deduction_credits.*.employee_id'           => 'nullable|integer|exists:employees,id',
            'payslip_deductions.payslip_deduction_credits.*.product_id'            => 'nullable|integer|exists:products,id',
            'payslip_deductions.payslip_deduction_credits.*.employee_credit_id'    => 'nullable|integer|exists:employee_credits,id',
            'payslip_deductions.payslip_deduction_credits.*.pieces'                => 'nullable|integer|min:0',
            'payslip_deductions.payslip_deduction_credits.*.price'                 => 'nullable|numeric|min:0',
            'payslip_deductions.payslip_deduction_credits.*.product_name'          => 'nullable|string',
            'payslip_deductions.payslip_deduction_credits.*.total_price'           => 'nullable|numeric|min:0',
            'payslip_deductions.payslip_deduction_credits.*.total_amount'          => 'nullable|numeric|min:0',
            'payslip_deductions.payslip_deduction_credits.*.created_at'            => 'nullable|date',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // 🔍 Check if payslip already exists
        $existingPayslip = Payslip::where('employee_id', $request->employee_id)
                            ->where('payroll_release_date', $request->payroll_release_date)
                            ->first();

        if ($existingPayslip) {
            return response()->json([
                'message' => 'Payslip already exists'
            ], 409); // Using a 409 Conflict status code is more appropriate here
        }
        // ✅ Transaction starts only if no duplicate payslip is found
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
            $dtrRecords = $request->input('payslip_dtr.payslip_dtr_record', []);
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

            // Payslip Holiday Summary
            $dtrHolidaysSummary = $request->input('payslip_holiday_summary.dtr_summary', []);
            foreach ($dtrHolidaysSummary as $summary) {
                PayslipHolidaySummary::create([
                    'payslip_id'            => $payslip->id,
                    'label'                 => $summary['id'],
                    'additional_pay'        => $summary['additionalPay'],
                    'date'                  => $summary['date'],
                    'holiday_rate'          => $summary['holidayRateText'],
                    'holiday_type'          => $summary['holidayType'],
                    'type'                  => $summary['type'],
                    'worked_hours'          => $summary['workedHours'],
                ]);
            }

            // Payslip Deductions
            $payslipDeductions = PayslipDeductions::create([
                'payslip_id'                => $payslip->id,
                'benefits_total'            => $request->payslip_deductions['benefits_total'],
                'cash_advance_total'        => $request->payslip_deductions['cash_advance_total'],
                'credit_total'              => $request->payslip_deductions['credit_total'],
                'employee_charge_total'     => $request->payslip_deductions['employee_charge_total'],
                'total_deduction'           => $request->payslip_deductions['total_deductions'],
                'uniform_total'             => $request->payslip_deductions['uniform_total'],
            ]);

            // Payslip Deduction Benefits
            $deductionBenefits = $request->input('payslip_deductions.payslip_deduction_benefits', []);
            PayslipDeductionBenefits::create([
                'payslip_deduction_id'      => $payslipDeductions->id,
                'employee_id'               => $request->employee_id,
                'hdmf'                      => $deductionBenefits['hdmf'] ?? 0, // Use array access and null coalescing
                'phic'                      => $deductionBenefits['phic'] ?? 0,
                'sss'                       => $deductionBenefits['sss'] ?? 0,
            ]);

            // Payslip Deduction CASH ADVANCE
            $cashAdvance = $request->input('payslip_deductions.payslip_deduction_ca', []);
            foreach ($cashAdvance as $ca) {

                // automically find and update the cash advance record
                $cashAdvance = CashAdvance::find($ca['id']);

                if ($cashAdvance) {

                    // Calculate the new remaining payments
                    $newRemainingPayments = $cashAdvance->remaining_payments - $cashAdvance->payments_per_payroll;

                    // Ensure remaining payments doesn't go below zero
                    $cashAdvance->remaining_payments = max(0, $newRemainingPayments);
                    $cashAdvance->save();

                    // Create the payslip deduction record
                    PayslipDeductionCa::create([
                        'payslip_deduction_id'      => $payslipDeductions->id,
                        'cash_advance_id'           => $ca['id'],
                        'employee_id'               => $ca['employee_id'],
                        'date'                      => Carbon::parse($ca['created_at'])->timezone('Asia/Manila'),
                        'amount'                    => $ca['amount'],
                        'number_of_payment'         => $ca['number_of_payments'],
                        'payment_per_payroll'       => $ca['payment_per_payroll'],
                        'remaining_payments'        => $ca['remaining_payments'],
                        'reason'                    => $ca['reason'],
                    ]);
                }
            }

            // Payslip Deduction CHARGES
            $deductionCharges = $request->input('payslip_deductions.payslip_deduction_charges', []);
            foreach ($deductionCharges as $charge) {
                PayslipDeductionCharges::create([
                    'payslip_deduction_id'      => $payslipDeductions->id,
                    'sales_report_id'           => $charge['id'],
                    'user_id'                   => $charge['user_id'],
                    'date'                      => Carbon::parse($charge['created_at'])->timezone('Asia/Manila'),
                    'branch_id'                 => $charge['branch_id'],
                    'charges_amount'            => $charge['charges_amount'],
                ]);
            }

            // Payslip Deductions Uniforms
            $deductionUniforms = $request->input('payslip_deductions.payslip_deduction_uniforms', []);

            foreach ($deductionUniforms as $deductionUniform) {
                $uniforms = Uniform::find($deductionUniform['id']);

                if (is_array($deductionUniform) && !empty($deductionUniform)) {

                    $newRemainingPayments = $uniforms->remaining_payments - $uniforms->payments_per_payroll;

                    $uniforms->remaining_payments = max(0, $newRemainingPayments);
                    $uniforms->save();

                    $payslipUniforms = PayslipDeductionUniforms::create([
                        'payslip_deduction_id'      => $payslipDeductions->id,
                        'uniform_id'                => $deductionUniform['id'],
                        'employee_id'               => $deductionUniform['employee_id'],
                        'date'                      => !empty($deductionUniform['created_at'])
                                                        ? Carbon::parse($deductionUniform['created_at'])->timezone('Asia/Manila')
                                                        : null,
                        'number_of_payments'        => $deductionUniform['number_of_payments'],
                        'payments_per_payroll'      => $deductionUniform['payments_per_payroll'],
                        'remaining_payments'        => $deductionUniform['remaining_payments'],
                        'total_amount'              => $deductionUniform['total_amount'],
                    ]);

                    // Pants
                     $deductionUniformsPants = $deductionUniform['pants'] ?? [];
                        if (is_array($deductionUniformsPants) && !empty($deductionUniformsPants)) {
                            foreach ($deductionUniformsPants as $pants) {
                                PayslipDeductionUniformPants::create([
                                    'payslip_deduction_uniform_id'  => $payslipUniforms->id,
                                    'uniform_pant_id'               => $pants['id'],
                                    'date'                          => Carbon::parse($pants['created_at'])->timezone('Asia/Manila'),
                                    'pcs'                           => $pants['pcs'],
                                    'price'                         => $pants['price'],
                                    'size'                          => $pants['size'],
                                ]);
                            }
                        }

                    // T-Shirts
                    $deductionUniformsTShirts = $deductionUniform['t_shirt'] ?? [];
                    if (!empty($deductionUniformsTShirts)) {
                        foreach ($deductionUniformsTShirts as $tShirt) {
                            PayslipDeductionUniformTshirt::create([
                                'payslip_deduction_uniform_id' => $payslipUniforms->id,
                                'uniform_tshirt_id'            => $tShirt['id'] ?? null,
                                'date'                         => !empty($tShirt['created_at'])
                                                                    ? Carbon::parse($tShirt['created_at'])->timezone('Asia/Manila')
                                                                    : null,
                                'pcs'                          => $tShirt['pcs'] ?? null,
                                'price'                        => $tShirt['price'] ?? null,
                                'size'                         => $tShirt['size'] ?? null,
                            ]);
                        }
                    }
                }
            }

            // Payslip Deductions Credits
            $deductionCredits = $request->input('payslip_deductions.payslip_deduction_credits', []);
            foreach ($deductionCredits as $deductionCredit) {

                // automically find and update the cash advance record
                $credit = EmployeeCredits::find($deductionCredit['employee_credit_id']);
                if ($credit) {
                    $credit->status = 'paid'; // set status to "paid"
                    $credit->save();
                }


                PayslipDeductionCredit::create([
                    'payslip_deduction_id'           => $payslipDeductions->id,
                    'branch_id'                      => $deductionCredit['branch_id'],
                    'sales_report_id'                => $deductionCredit['sales_report_id'],
                    'employee_credit_id'             => $deductionCredit['employee_credit_id'],
                    'employee_credit_product_id'     => $deductionCredit['id'],
                    'employee_id'                    => $deductionCredit['employee_id'],
                    'product_id'                     => $deductionCredit['product_id'],
                    'date'                           => Carbon::parse($deductionCredit['created_at'])->timezone('Asia/Manila'),
                    'pieces'                         => $deductionCredit['pieces'],
                    'price'                          => $deductionCredit['price'],
                    'product_name'                   => $deductionCredit['product_name'],
                    'total_price'                    => $deductionCredit['total_price'],
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
