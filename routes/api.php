<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RawMaterialController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\BranchEmployeeController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BranchProductController;
use App\Http\Controllers\BranchRawMaterialsReportController;
use App\Http\Controllers\BranchRecipeController;
use App\Http\Controllers\BranchReportController;
use App\Http\Controllers\CashAdvanceController;
use App\Http\Controllers\DailyTimeRecordController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\EmployeeAllowanceController;
use App\Http\Controllers\EmployeeBenefitController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDeductionController;
use App\Http\Controllers\EmploymentTypeController;
use App\Http\Controllers\InitialBakerReportController;
use App\Http\Controllers\InitialBakerreportsController;
use App\Http\Controllers\SalesReportsController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\UniformController;
use App\Models\Branch;
use App\Models\BranchRawMaterialsReport;
use App\Models\BranchRecipe;
use App\Models\CashAdvance;
use App\Models\DailyTimeRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('register',[ApiController::class, 'register']);
Route::post('login',[ApiController::class, 'login']);
Route::group([
    "middleware" => ['auth:sanctum']
], function(){
    //profile
    Route::get('profile',[ApiController::class, 'profile']);

    //
    Route::get('logout',[ApiController::class, 'logout']);

    // Route::post('refresh-tokens',[ApiController::class, 'logout']);


});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('users', UserController::class);
Route::apiResource('raw-materials', RawMaterialController::class);
Route::apiResource('warehouses', WarehouseController::class);
Route::apiResource('branches', BranchController::class);
Route::apiResource('products', ProductController::class);
Route::apiResource('recipes', RecipeController::class);
Route::apiResource('branch-raw-materials', BranchRawMaterialsReportController::class);
Route::apiResource('initial-baker-report', InitialBakerreportsController::class);
Route::apiResource('branch-products', BranchProductController::class);
Route::apiResource('sales-report', SalesReportsController::class);
Route::apiResource('branch-production-report', BranchReportController::class);
Route::apiResource('employment-types', EmploymentTypeController::class);
Route::apiResource('employee', EmployeeController::class);
Route::apiResource('branchEmployee', BranchEmployeeController::class);
Route::apiResource('device', DeviceController::class);
Route::apiResource('dtr', DailyTimeRecordController::class);
Route::apiResource('employee-allowance', EmployeeAllowanceController::class);
Route::apiResource('employee-benefit', EmployeeBenefitController::class);
Route::apiResource('cash-advance', CashAdvanceController::class);
Route::apiResource('uniform', UniformController::class);
Route::apiResource('branch-recipe', BranchRecipeController::class);

Route::post('search-allowance', [EmployeeAllowanceController::class, 'searchAllowance']);
Route::post('search-benefit', [EmployeeBenefitController::class, 'searchBenefit']);
Route::post('search-uniform', [UniformController::class, 'searchUniform']);
Route::post('search-cash-advance', [CashAdvanceController::class, 'searchCashAdvances']);
Route::post('check-uuid-id', [DailyTimeRecordController::class, 'checkIdAndUuid']);
Route::post('check-dtr-status', [DailyTimeRecordController::class, 'checkDtrStatus']);
Route::post('markTimeIn', [DailyTimeRecordController::class, 'markTimeIn']);
Route::post('markTimeOut', [DailyTimeRecordController::class, 'markTimeOut']);
Route::post('checkBreakStatus', [DailyTimeRecordController::class, 'checkBreakStatus']);
Route::post('break', [DailyTimeRecordController::class, 'break']);
Route::post('checkLunchBreakStatus', [DailyTimeRecordController::class, 'checkLunchBreakStatus']);
Route::post('lunchBreak', [DailyTimeRecordController::class, 'lunchBreak']);
Route::post('checkDevice', [DeviceController::class, 'checkDevice']);
Route::post('confirm-initial-baker-report/{id}', [InitialBakerreportsController::class, 'confirmReport']);
Route::post('decline-initial-baker-report/{id}', [InitialBakerreportsController::class, 'declineReport']);
Route::post('search-branches-by-id', [BranchProductController::class, 'searchBranchId' ]);
Route::post('search-user', [UserController::class, 'searchUser' ]);
Route::post('search', [UserController::class, 'search' ]);
Route::post('search-user-with-branchID', [BranchEmployeeController::class, 'searchUserWithBranch' ]);
Route::post('search-branch-employee', [BranchEmployeeController::class, 'searchBranchEmployee' ]);
Route::post('search-branch-rawMaterials', [BranchRawMaterialsReport::class, 'searchBranchRawMaterials' ]);
Route::post('search-products', [BranchProductController::class, 'searchProducts']);
Route::post('search-branch',[ BranchController::class, 'searchBranch']);
Route::post('search-employees', [EmployeeController::class, 'searchEmployees']);
Route::post('searchEmployeesWithDesignation', [EmployeeController::class, 'searchEmployeesWithDesignation']);
Route::post('dtr-data', [DailyTimeRecordController::class, 'getDTRData']);
Route::post('search-drt', [DailyTimeRecordController::class, 'searchDTR']);
Route::post('saveOvertime', [DailyTimeRecordController::class, 'saveOvertime']);

Route::put('update-employee-birthdate/{id}', [EmployeeController::class, 'updateEmployeeBirthdate']);
Route::put('update-employee-phone/{id}', [EmployeeController::class, 'updateEmployeePhone']);
Route::put('update-employee-address/{id}', [EmployeeController::class, 'updateEmployeeAddress']);
Route::put('update-employee-employmentType/{id}', [EmployeeController::class, 'updateEmployeeEmploymentType']);
Route::put('update-employee-fullname/{id}', [EmployeeController::class, 'updateEmployeeFullname']);
Route::put('user-email/{id}', [UserController::class, 'updateEmail']);
Route::put('update-user-profile/{userId}', [ApiController::class, 'updateUser']);
Route::put('update-name/{id}', [RecipeController::class, 'updateName']);
Route::put('update-target/{id}', [BranchRecipeController::class, 'updateTarget']);
Route::put('update-status/{id}', [RecipeController::class, 'updateStatus']);
Route::put('branch-update-status/{id}', [BranchRecipeController::class, 'branchUpdateStatus']);
Route::put('update-branch-products/{id}', [BranchProductController::class, 'updatePrice' ]);
Route::put('update-branch-products-total-quantity/{id}', [BranchProductController::class, 'updateTotatQuatity' ]);
Route::put('update-branch-rawMaterials/{id}', [BranchRawMaterialsReportController::class, 'updateStocks' ]);

Route::get('branch/{branchId}/salesReport', [SalesReportsController::class, 'fetchBranchSalesReport']);
Route::get('get-bread-production', [InitialBakerreportsController::class, 'getInitialReportsData']);
Route::get('branch/{branchId}/rawMaterials',[ BranchRawMaterialsReportController::class, 'getRawMaterials']);
Route::get('branch/{branchId}/bakerDoughReport',[ InitialBakerreportsController::class, 'fetchDoughReports']);
Route::get('branch/{userId}/bakerReport',[ InitialBakerreportsController::class, 'getReportsByUserId']);
Route::get('ingredients',[ RawMaterialController::class, 'fetchRawMaterialsIngredients']);
Route::get('bread-products', [ProductController::class, 'fetchBreadProducts']);
Route::get('search-branch',[ BranchController::class, 'searchBranch']);
Route::get('search-recipes',[ RecipeController::class, 'searchRecipe']);
Route::get('branch-recipe-search',[ BranchRecipeController::class, 'branchSearchRecipe']);
Route::get('branches/{branchId}/recipes', [BranchRecipeController::class, 'getBranchRecipe']);
Route::get('branches/{branchId}/products', [BranchProductController::class, 'getProducts']);
Route::get('branches/{branchId}/production-report', [BranchReportController::class, 'fetchBranchReport']);
Route::get('user/{userId}', [UserController::class, 'fetchUserById']);
Route::get('search-products', [ProductController::class, 'searchProducts']);
Route::get('search-rawMaterials', [RawMaterialController::class, 'searchRawMaterials']);
Route::get('fetchBranchWithEmployee', [BranchController::class, 'fetchBranchWithEmployee']);
Route::get('fetchWarehouseWithEmployee', [WarehouseController::class, 'fetchWarehouseWithEmployee']);
Route::get('fetchAllEmployee', [EmployeeController::class, 'fetchAllEmployee']);
Route::get('fetchSupervisorUnderBranch/{employee_id}', [SupervisorController::class, 'fetchSupervisorUnderBranch']);
Route::get('fetchEmployeeWithEmploymentType', [EmployeeController::class, 'fetchEmployeeWithEmploymentType']);

