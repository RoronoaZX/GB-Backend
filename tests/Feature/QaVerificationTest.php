<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Device;
use App\Models\DailyTimeRecord;
use App\Models\NestleSalesReport;
use App\Models\Branch;
use App\Models\Product;
use App\Models\BranchProduct;
use App\Models\SalesReports;
use App\Models\HistoryLog;
use App\Models\EmployeeSaleschargesReport;
use App\Models\CakeReport;
use App\Models\Employee;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class QaVerificationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test BUG-31: Supervisor can decline Nestle sales report.
     */
    public function test_supervisor_can_decline_nestle_sales_report(): void
    {
        $supervisorUser = User::where('email', 'supervisordev@gmail.com')->firstOrFail();
        
        $branch = Branch::first() ?? Branch::create(['name' => 'Test Branch']);
        $product = Product::create([
            'name' => 'Nestle Ice Cream Test',
            'category' => 'nestle'
        ]);

        $branchProduct = BranchProduct::create([
            'branches_id' => $branch->id,
            'product_id' => $product->id,
            'category' => 'nestle',
            'price' => 50,
            'beginnings' => 20,
            'new_production' => 0,
            'total_quantity' => 20
        ]);

        $parentReport = SalesReports::create([
            'branch_id' => $branch->id,
            'user_id' => $supervisorUser->id,
            'products_total_sales' => 100,
            'expenses_total' => 0,
            'denomination_total' => 100,
            'charges_amount' => 0,
            'over_total' => 0,
            'credit_total' => 0,
        ]);

        $nestleReport = NestleSalesReport::create([
            'branch_id' => $branch->id,
            'user_id' => $supervisorUser->id,
            'product_id' => $product->id,
            'sales_report_id' => $parentReport->id,
            'beginnings' => 20,
            'remaining' => 15,
            'price' => 50,
            'sold' => 5,
            'out' => 0,
            'sales' => 250,
            'added_stocks' => 0,
            'status' => 'pending',
        ]);

        // Decline payload
        $payload = [
            'id' => $nestleReport->id,
            'employee_id' => $supervisorUser->employee_id,
            'branches_id' => $branch->id,
            'remaining' => 18, // New beginnings to be set on decline
            'type' => 'nestle',
            'reason' => 'Inventory count mismatch',
            'status' => 'declined'
        ];

        $response = $this->actingAs($supervisorUser)->postJson('/api/decline-product-sales-report', $payload);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Nestlesales report declined successfully.');

        // Assert database updates
        $nestleReport->refresh();
        $this->assertEquals('declined', $nestleReport->status);
        $this->assertEquals($supervisorUser->employee_id, $nestleReport->handled_by);
        $this->assertEquals('Inventory count mismatch', $nestleReport->reason);

        // Assert inventory restoration
        $branchProduct->refresh();
        $this->assertEquals(18, $branchProduct->beginnings);
        $this->assertEquals(18, $branchProduct->total_quantity);

        // Assert HistoryLog created
        $history = HistoryLog::where('user_id', $supervisorUser->id)
            ->where('action', 'declined')
            ->first();

        $this->assertNotNull($history);
        $this->assertStringContainsString('Nestle: Nestle Ice Cream Test', $history->name);
    }

    /**
     * Test CONS-06: Overtime approval when ot_status is null or pending, and rejection when not pending.
     */
    public function test_overtime_approval_with_various_statuses(): void
    {
        $supervisorUser = User::where('email', 'supervisordev@gmail.com')->firstOrFail();
        $employee = Employee::first() ?? Employee::create([
            'firstname' => 'Test',
            'lastname' => 'Employee',
            'position' => 'Cashier',
            'status' => 'Current'
        ]);

        // Case 1: ot_status is null
        $dtrNull = DailyTimeRecord::create([
            'employee_id' => $employee->id,
            'date' => '2026-05-21',
            'time_in' => '2026-05-21 08:00:00',
            'time_out' => '2026-05-21 17:00:00',
            'ot_status' => null,
        ]);

        $payload = [
            'id' => $dtrNull->id,
            'approved_by' => $supervisorUser->employee_id
        ];

        $response = $this->actingAs($supervisorUser)->postJson('/api/approveOvertime', $payload);
        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Overtime request approved successfully!');
        
        $dtrNull->refresh();
        $this->assertEquals('approved', $dtrNull->ot_status);

        // Case 2: ot_status is 'pending'
        $dtrPending = DailyTimeRecord::create([
            'employee_id' => $employee->id,
            'date' => '2026-05-21',
            'time_in' => '2026-05-21 08:00:00',
            'time_out' => '2026-05-21 17:00:00',
            'ot_status' => 'pending',
        ]);

        $payloadPending = [
            'id' => $dtrPending->id,
            'approved_by' => $supervisorUser->employee_id
        ];

        $responsePending = $this->actingAs($supervisorUser)->postJson('/api/approveOvertime', $payloadPending);
        $responsePending->assertStatus(200);
        
        $dtrPending->refresh();
        $this->assertEquals('approved', $dtrPending->ot_status);

        // Case 3: ot_status is 'approved' (should fail)
        $payloadFail = [
            'id' => $dtrPending->id, // now approved
            'approved_by' => $supervisorUser->employee_id
        ];

        $responseFail = $this->actingAs($supervisorUser)->postJson('/api/approveOvertime', $payloadFail);
        $responseFail->assertStatus(400);
        $responseFail->assertJsonPath('message', 'Overtime request is not pending or has already been processed.');
    }

    /**
     * Test CRIT-07: adminStoreSalesReport has database transaction rollback on exception, and commits on success.
     */
    public function test_admin_store_sales_report_transactions(): void
    {
        $adminUser = User::where('email', 'johndoe@example.com')->firstOrFail();
        $branch = Branch::first() ?? Branch::create(['name' => 'Test Branch']);
        $product = Product::first() ?? Product::create(['name' => 'Test Bread', 'category' => 'bread']);

        BranchProduct::create([
            'branches_id' => $branch->id,
            'product_id' => $product->id,
            'category' => 'bread',
            'price' => 10,
            'beginnings' => 100,
            'new_production' => 0,
            'total_quantity' => 100
        ]);

        $employee = Employee::first() ?? Employee::create([
            'firstname' => 'Test',
            'lastname' => 'Employee',
            'position' => 'Cashier',
            'status' => 'Current'
        ]);

        // Base payload
        $basePayload = [
            'branch_id' => $branch->id,
            'user_id' => $adminUser->id,
            'denomination_total' => 100.00,
            'expenses_total' => 0.00,
            'products_total_sales' => 100.00,
            'charges_amount' => 10.00,
            'over_total' => 0.00,
            'credit_total' => 0.00,
            'employee_in_shift' => [
                ['employee_id' => $employee->id]
            ],
            'breadReports' => [
                [
                    'branch_id' => $branch->id,
                    'user_id' => $adminUser->id,
                    'product_id' => $product->id,
                    'beginnings' => 100,
                    'remaining' => 90,
                    'price' => 10.00,
                    'bread_sold' => 10,
                    'sales' => 100.00,
                    'branch_new_production' => 0,
                    'new_production' => 0,
                    'added_stocks' => 0,
                    'bread_out' => 0,
                    'total' => 100,
                ]
            ],
            'denominationReports' => [
                'one_thousand' => 0,
                'five_hundred' => 0,
                'two_hundred' => 0,
                'one_hundred' => 1,
                'fifty' => 0,
                'twenty' => 0,
                'ten' => 0,
                'five' => 0,
                'one' => 0,
                'point_twenty_five' => 0,
                'point_five' => 0,
                'total_amount' => 100,
            ]
        ];

        // 1. Transaction ROLLBACK Test (with invalid cake report ID)
        $payloadWithInvalidCake = $basePayload;
        $payloadWithInvalidCake['cakeReports'] = [
            [
                'cake_report_id' => 99999, // Non-existent ID
                'sales_status' => 'sold'
            ]
        ];

        // Count database rows before
        $salesReportsCountBefore = SalesReports::count();
        $chargesCountBefore = EmployeeSaleschargesReport::count();

        $responseRollback = $this->actingAs($adminUser)->postJson('/api/admin-sales-report', $payloadWithInvalidCake);

        $responseRollback->assertStatus(500);
        $responseRollback->assertJsonPath('message', 'Failed to save sales report. All changes have been rolled back.');

        // Assert database stayed exactly the same (clean, no orphan rows)
        $this->assertEquals($salesReportsCountBefore, SalesReports::count());
        $this->assertEquals($chargesCountBefore, EmployeeSaleschargesReport::count());

        // 2. Transaction COMMIT Test (with valid payload)
        // Let's create a valid cake report first so we can reference it
        $cakeReport = CakeReport::create([
            'branch_id' => $branch->id,
            'user_id' => $adminUser->id,
            'name' => 'Chocolate Cake',
            'layers' => 1,
            'confirmation_status' => 'confirmed',
            'price' => 200,
        ]);

        $payloadWithValidCake = $basePayload;
        $payloadWithValidCake['cakeReports'] = [
            [
                'cake_report_id' => $cakeReport->id,
                'sales_status' => 'sold'
            ]
        ];

        $responseCommit = $this->actingAs($adminUser)->postJson('/api/admin-sales-report', $payloadWithValidCake);

        if ($responseCommit->status() !== 200) {
            dd($responseCommit->json());
        }

        $responseCommit->assertStatus(200);
        $responseCommit->assertJsonPath('message', 'Sales report saved successfully.');

        // Assert database rows increased
        $this->assertEquals($salesReportsCountBefore + 1, SalesReports::count());
        $this->assertEquals($chargesCountBefore + 1, EmployeeSaleschargesReport::count());
        
        // Assert the cake report status was updated
        $cakeReport->refresh();
        $this->assertEquals('sold', $cakeReport->sales_status);
    }

    /**
     * Test INEFF-02: getRecievePremix uses database-level pagination.
     */
    public function test_receive_premix_pagination(): void
    {
        $adminUser = User::where('email', 'johndoe@example.com')->firstOrFail();

        $employee = Employee::first();
        if (!$employee) {
            $employmentType = \App\Models\EmploymentType::first() ?? \App\Models\EmploymentType::create(['name' => 'Full Time']);
            $employee = Employee::create([
                'firstname' => 'Premix',
                'lastname' => 'Worker',
                'position' => 'Baker',
                'status' => 'Current',
                'employment_type_id' => $employmentType->id,
            ]);
        }

        $warehouse = \App\Models\Warehouse::create([
            'name' => 'Warehouse Test Pagination ' . uniqid(),
            'location' => 'City Center',
            'employee_id' => $employee->id,
            'status' => 'active'
        ]);

        $branch = Branch::create([
            'name' => 'Branch Test Pagination ' . uniqid(),
            'warehouse_id' => $warehouse->id,
            'employee_id' => $employee->id,
        ]);

        $recipe = \App\Models\Recipe::first() ?? \App\Models\Recipe::create([
            'name' => 'Test Bread Recipe',
            'category' => 'bread',
        ]);
        $branchRecipe = \App\Models\BranchRecipe::create([
            'branch_id' => $branch->id,
            'recipe_id' => $recipe->id,
            'status' => 'active',
            'target' => 10,
        ]);
        $branchPremix = \App\Models\BranchPremix::create([
            'branch_id' => $branch->id,
            'branch_recipe_id' => $branchRecipe->id,
            'name' => 'Chocolate Premix ' . uniqid(),
            'category' => 'bread',
            'status' => 'active',
            'available_stocks' => 10,
        ]);

        // Create 7 request premixes with received status
        for ($i = 1; $i <= 7; $i++) {
            \App\Models\RequestPremix::create([
                'branch_premix_id' => $branchPremix->id,
                'warehouse_id' => $warehouse->id,
                'employee_id' => $employee->id,
                'name' => "Request Premix {$i}",
                'category' => 'bread',
                'status' => 'received',
                'quantity' => $i,
            ]);
        }

        // Fetch page 1 with per_page = 5
        $response1 = $this->actingAs($adminUser)->getJson("/api/get-receive-premix/{$warehouse->id}?page=1&per_page=5");
        $response1->assertStatus(200);
        
        $data1 = $response1->json();
        $this->assertEquals(7, $data1['total']);
        $this->assertEquals(5, count($data1['data']));
        $this->assertEquals(1, $data1['current_page']);
        $this->assertEquals(2, $data1['last_page']);

        // Fetch page 2 with per_page = 5
        $response2 = $this->actingAs($adminUser)->getJson("/api/get-receive-premix/{$warehouse->id}?page=2&per_page=5");
        $response2->assertStatus(200);
        
        $data2 = $response2->json();
        $this->assertEquals(2, count($data2['data']));
        $this->assertEquals(2, $data2['current_page']);
    }

    /**
     * Test CRIT-11: Broken Payslip Uniform Deductions Loop success.
     */
    public function test_payslip_uniform_deductions_loop_success(): void
    {
        $adminUser = User::where('email', 'johndoe@example.com')->firstOrFail();

        $employee = Employee::first();
        if (!$employee) {
            $employmentType = \App\Models\EmploymentType::first() ?? \App\Models\EmploymentType::create(['name' => 'Full Time']);
            $employee = Employee::create([
                'firstname' => 'Uniform',
                'middlename' => 'TesterMiddle',
                'lastname' => 'Tester',
                'birthdate' => '1990-06-15',
                'phone' => '09351212121',
                'address' => 'Street 123123',
                'sex' => 'Male',
                'position' => 'Baker',
                'status' => 'Current',
                'employment_type_id' => $employmentType->id,
            ]);
        }

        $dtr = DailyTimeRecord::create([
            'employee_id' => $employee->id,
            'date' => '2026-05-21',
            'time_in' => '2026-05-21 08:00:00',
            'time_out' => '2026-05-21 17:00:00',
            'device_uuid_in' => 'test-device',
            'device_uuid_out' => 'test-device',
        ]);

        $uniform = \App\Models\Uniform::create([
            'employee_id' => $employee->id,
            'number_of_payments' => 4,
            'payments_per_payroll' => 500.00,
            'total_amount' => 2000.00,
            'remaining_payments' => 2000.00,
        ]);

        $pants = \App\Models\UniformPants::create([
            'uniform_id' => $uniform->id,
            'size' => 'M',
            'pcs' => 1,
            'price' => 250.00,
        ]);

        $tshirt = \App\Models\UniformTshirt::create([
            'uniform_id' => $uniform->id,
            'size' => 'M',
            'pcs' => 1,
            'price' => 250.00,
        ]);

        $payload = [
            'employee_id' => $employee->id,
            'from' => '2026-05-01',
            'to' => '2026-05-15',
            'payroll_release_date' => '2026-05-18',
            'rate_per_day' => 500.00,
            'total_days' => 12,
            'uniform_balance' => 1500.00,
            'credit_balance' => 0.00,
            'cash_advance_balance' => 0.00,
            'total_earnings' => 6000.00,
            'total_deductions' => 500.00,
            'net_income' => 5500.00,
            'payslip_earnings' => [
                'working_hours_pay' => 6000.00,
                'overtime_pay' => 0.00,
                'night_diff_pay' => 0.00,
                'holidays_pay' => 0.00,
                'allowances_pay' => 0.00,
                'incentives_pay' => 0.00,
                'undertime_pay' => 0.00,
            ],
            'payslip_dtr' => [
                'from' => '2026-05-01',
                'end' => '2026-05-15',
                'release_date' => '2026-05-18',
                'payslip_dtr_record' => [
                    [
                        'id' => $dtr->id,
                        'device_uuid_in' => 'test-device',
                        'device_uuid_out' => 'test-device',
                        'employee_id' => $employee->id,
                        'time_in' => '2026-05-21 08:00:00',
                        'time_out' => '2026-05-21 17:00:00',
                    ]
                ]
            ],
            'payslip_holiday_summary' => [
                'dtr_summary' => [
                    [
                        'id' => '1',
                        'additionalPay' => 0.00,
                        'date' => '2026-05-21',
                        'holidayRateText' => 'Regular',
                        'holidayType' => 'Regular',
                        'type' => 'regular',
                        'workedHours' => 8.00,
                    ]
                ]
            ],
            'payslip_deductions' => [
                'benefits_total' => 0.00,
                'cash_advance_total' => 0.00,
                'credit_total' => 0.00,
                'employee_charge_total' => 0.00,
                'total_deductions' => 500.00,
                'uniform_total' => 500.00,
                'payslip_deduction_uniforms' => [
                    [
                        'id' => $uniform->id,
                        'employee_id' => $employee->id,
                        'created_at' => '2026-05-22 14:00:00',
                        'number_of_payments' => 4,
                        'payments_per_payroll' => 500.00,
                        'remaining_payments' => 2000.00,
                        'total_amount' => 2000.00,
                        'pants' => [
                            [
                                'id' => $pants->id,
                                'created_at' => '2026-05-22 14:00:00',
                                'pcs' => 1,
                                'price' => 250.00,
                                'size' => 'M',
                            ]
                        ],
                        't_shirt' => [
                            [
                                'id' => $tshirt->id,
                                'created_at' => '2026-05-22 14:00:00',
                                'pcs' => 1,
                                'price' => 250.00,
                                'size' => 'M',
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->actingAs($adminUser)->postJson('/api/payslip', $payload);

        $response->assertStatus(200);

        $uniform->refresh();
        $this->assertEquals(1500.00, $uniform->remaining_payments);
    }

    /**
     * Test CRIT-PMX-02 & CRIT-PMX-01: receive-premix creates FIFO batch records and rolls back transaction on status abort.
     */
    public function test_receive_premix_creates_fifo_batches_and_aborts_on_invalid_status(): void
    {
        $adminUser = User::where('email', 'johndoe@example.com')->firstOrFail();
        $employee = Employee::first();
        if (!$employee) {
            $employmentType = \App\Models\EmploymentType::first() ?? \App\Models\EmploymentType::create(['name' => 'Full Time']);
            $employee = Employee::create([
                'firstname' => 'Test',
                'lastname' => 'Worker',
                'position' => 'Baker',
                'status' => 'Current',
                'employment_type_id' => $employmentType->id,
            ]);
        }

        $warehouse = \App\Models\Warehouse::create([
            'name' => 'Test Warehouse ' . uniqid(),
            'location' => 'City',
            'employee_id' => $employee->id,
            'status' => 'active'
        ]);

        $branch = Branch::create([
            'name' => 'Test Branch ' . uniqid(),
            'warehouse_id' => $warehouse->id,
            'employee_id' => $employee->id,
        ]);

        $rawMaterial = \App\Models\RawMaterial::create([
             'name' => 'Test Flour ' . uniqid(),
             'code' => 'RAW-FLOUR-' . uniqid(),
             'category' => 'ingredients',
             'unit' => 'grams',
         ]);
 
         $rawMaterialsDelivery = \App\Models\RawMaterialsDelivery::create([
             'employee_id'        => $employee->id,
             'from_id'            => 0,
             'from_designation'   => 'Supplier',
             'from_name'          => 'Test Supplier',
             'to_id'              => $warehouse->id,
             'to_designation'     => 'Warehouse',
             'remarks'            => 'Initial stock delivery',
             'status'             => 'confirmed',
         ]);

         $deliveryUnit = \App\Models\DeliveryStocksUnit::create([
             'rm_delivery_id'            => $rawMaterialsDelivery->id,
             'raw_material_id'           => $rawMaterial->id,
             'unit_type'                 => 'grams',
             'quantity'                  => 1000,
             'price_per_unit'            => 50,
             'price_per_gram'            => 0.05,
             'gram'                      => 1000,
             'pcs'                       => 0,
             'kilo'                      => 1,
             'total_grams'               => 1000,
             'category'                  => 'ingredients',
         ]);

         \App\Models\WarehouseRmStocks::create([
             'warehouse_id' => $warehouse->id,
             'raw_material_id' => $rawMaterial->id,
             'price_per_gram' => 0.05,
             'quantity' => 1000,
             'total_grams' => 1000,
             'delivery_su_id' => $deliveryUnit->id,
         ]);

        $recipe = \App\Models\Recipe::first() ?? \App\Models\Recipe::create([
            'name' => 'Test Recipe',
            'category' => 'bread',
        ]);

        $branchRecipe = \App\Models\BranchRecipe::create([
            'branch_id' => $branch->id,
            'recipe_id' => $recipe->id,
            'status' => 'active',
            'target' => 10,
        ]);

        $branchPremix = \App\Models\BranchPremix::create([
            'branch_id' => $branch->id,
            'branch_recipe_id' => $branchRecipe->id,
            'name' => 'Test Premix ' . uniqid(),
            'category' => 'bread',
            'status' => 'active',
            'available_stocks' => 0,
        ]);

        $requestPremix = \App\Models\RequestPremix::create([
            'branch_premix_id' => $branchPremix->id,
            'warehouse_id' => $warehouse->id,
            'employee_id' => $employee->id,
            'name' => 'Test Request',
            'category' => 'bread',
            'status' => 'to receive',
            'quantity' => 2,
        ]);

        $payload = [
            'request_premix_id' => $requestPremix->id,
            'branch_premix_id' => $branchPremix->id,
            'employee_id' => $employee->id,
            'status' => 'received',
            'notes' => 'Received pre-mix packages.',
            'quantity' => 2,
            'warehouse_id' => $warehouse->id,
            'branch_id' => $branch->id,
            'ingredients' => [
                [
                    'ingredients_id' => $rawMaterial->id,
                    'total_quantity' => 500,
                ]
            ]
        ];

        $response = $this->actingAs($adminUser)->postJson('/api/receive-premix', $payload);

        $response->assertStatus(200);

        // 1. Assert BranchRmStocks batch created
        $stock = \App\Models\BranchRmStocks::where([
            'branch_id' => $branch->id,
            'raw_material_id' => $rawMaterial->id,
        ])->first();

        $this->assertNotNull($stock);
        $this->assertEquals(0.05, $stock->price_per_gram);
        $this->assertEquals(500, $stock->quantity);

        // 2. Assert BranchRawMaterialsReport summary updated
        $report = \App\Models\BranchRawMaterialsReport::where([
            'branch_id' => $branch->id,
            'ingredients_id' => $rawMaterial->id,
        ])->first();
        $this->assertNotNull($report);
        $this->assertEquals(500, $report->total_quantity);

        // 3. Try to receive it again (status is now 'received', so it should fail)
        $responseFail = $this->actingAs($adminUser)->postJson('/api/receive-premix', $payload);

        $responseFail->assertStatus(400);

        // 4. Assert stock was NOT incremented again (transaction rollback on abort)
        $stock->refresh();
        $this->assertEquals(500, $stock->quantity);
    }

    /**
     * Test MED-PMX-01: Confirming baker report decrements BranchPremix available stocks.
     */
    public function test_confirm_baker_report_decrements_branch_premix_stocks(): void
    {
        $adminUser = User::where('email', 'johndoe@example.com')->firstOrFail();
        $employee = Employee::first();
        if (!$employee) {
            $employmentType = \App\Models\EmploymentType::first() ?? \App\Models\EmploymentType::create(['name' => 'Full Time']);
            $employee = Employee::create([
                'firstname' => 'Test',
                'lastname' => 'Worker',
                'position' => 'Baker',
                'status' => 'Current',
                'employment_type_id' => $employmentType->id,
            ]);
        }

        $warehouse = \App\Models\Warehouse::create([
            'name' => 'Test Warehouse ' . uniqid(),
            'location' => 'City',
            'employee_id' => $employee->id,
            'status' => 'active'
        ]);

        $branch = Branch::create([
            'name' => 'Test Branch ' . uniqid(),
            'warehouse_id' => $warehouse->id,
            'employee_id' => $employee->id,
        ]);

        $recipe = \App\Models\Recipe::first() ?? \App\Models\Recipe::create([
            'name' => 'Test Recipe',
            'category' => 'bread',
        ]);

        $branchRecipe = \App\Models\BranchRecipe::create([
            'branch_id' => $branch->id,
            'recipe_id' => $recipe->id,
            'status' => 'active',
            'target' => 10,
        ]);

        $branchPremix = \App\Models\BranchPremix::create([
            'branch_id' => $branch->id,
            'branch_recipe_id' => $branchRecipe->id,
            'name' => 'Test Premix ' . uniqid(),
            'category' => 'bread',
            'status' => 'active',
            'available_stocks' => 10, // Initial pre-mix stock
        ]);

        $bakerReport = \App\Models\InitialBakerreports::create([
            'branch_id' => $branch->id,
            'user_id' => $adminUser->id,
            'branch_recipe_id' => $branchRecipe->id,
            'recipe_category' => 'Dough',
            'status' => 'pending',
            'kilo' => 3, // Consumes 3 units of premix
            'over' => 0,
            'short' => 0,
            'target' => 30,
            'actual_target' => 30,
        ]);

        $response = $this->actingAs($adminUser)->postJson("/api/confirm-initial-baker-report/{$bakerReport->id}");

        $response->assertStatus(200);

        // Assert that available stocks were decremented by 3
        $branchPremix->refresh();
        $this->assertEquals(7.00, $branchPremix->available_stocks);
    }
}
