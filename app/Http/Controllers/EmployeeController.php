<?php

namespace App\Http\Controllers;

use App\Models\BranchEmployee;
use App\Models\Employee;
use App\Models\WarehouseEmployee;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException; // Import the exception class
use PhpParser\Node\Stmt\TryCatch;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = Employee::orderBy('created_at', 'desc')
            ->take(7)
            ->get();

        return response()->json($employees);
    }

    public function fetchAllEmployee()
    {
        $employee = Employee::orderBy('created_at', 'desc')->get();
        return response()->json($employee, 201);
    }

    public function fetchEmployeeWithEmploymentType(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 5);
        $search = $request->query('search', '');

        $query = Employee::with(
            'userDesignation',
            'employmentType',
            'branchEmployee.branch',
            'warehouseEmployee.warehouse'
            )
            ->where('position', '!=', 'super admin');

        // $employees = Employee::with('employmentType')->orderBy('created_at', 'desc')->take(7)->get();
        // return response()->json($employees, 201);

        if (!empty($search)) {
            $query->where(function ($q) use ($search){
                $q->where('firstname', 'like', "%$search%")
                ->orWhere('lastname', 'like', "%$search%");
            });
        }

        if ($perPage == 0) {
            $data = $query->get();
            return response()->json([
                'data' => $data,
                'total' => $data->count(),
                'per_page' => $data->count(),
                'current_page' => 1,
                'last_page' => 1
            ]);
        }

        // ✅ Use built-in paginate method for server-side pagination
        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($paginated);
    }

    // public function fetchEmployeeWithEmploymentTypeAndDesignation(Request $request)
    // {
    //     $page = $request->get('page', 0);
    //     $perPage = $request->get('per_page', 0);
    //     $search = $request->query('search', '');

    //     // ✅ The main change is here: Eager load all necessary nested relationships.
    //     $query = Employee::with([
    //         'userDesignation',
    //         'employmentType',           // The employee's employment type
    //         'branchEmployee.branch',    // The employee's branch assignment AND the branch details
    //         'warehouseEmployee.warehouse' // The employee's warehouse assignment AND the warehouse details
    //     ])
    //     ->where('position', '!=', 'super admin')
    //     ->orderBy('created_at', 'desc'); // It's good practice to have a default order

    //     if (!empty($search)) {
    //         $query->where(function ($q) use ($search) {
    //             $q->where('firstname', 'like', "%$search%")
    //             ->orWhere('lastname', 'like', "%$search%");

    //             // You can even make the search more powerful by searching the designation name!
    //             // Note: This requires a more advanced query using whereHas.
    //             // Example below for future reference.
    //             /*
    //             $q->orWhereHas('branchEmployee.branch', function ($branchQuery) use ($search) {
    //                 $branchQuery->where('name', 'like', "%$search%");
    //             })->orWhereHas('warehouseEmployee.warehouse', function ($warehouseQuery) use ($search) {
    //                 $warehouseQuery->where('name', 'like', "%$search%");
    //             });
    //             */
    //         });
    //     }

    //     if ($perPage == 0) {
    //         $data = $query->get();
    //         // The 'designation_name' and 'designation_type' attributes from your Employee model
    //         // will be automatically added to the JSON response because of the $appends property.
    //         return response()->json([
    //             'data' => $data,
    //             'total' => $data->count(),
    //             'per_page' => $data->count(),
    //             'current_page' => 1,
    //             'last_page' => 1
    //         ]);
    //     }

    //     // The paginate method will execute the query with the eager loading.
    //     $paginated = $query->paginate($perPage, ['*'], 'page', $page);

    //     return response()->json($paginated);
    // }

    public function fetchEmployeeWithEmploymentTypeAndDesignation(Request $request)
    {
        $search = $request->query('search', '');

        $query = Employee::with([
            'userDesignation',
            'employmentType',
            'branchEmployee.branch',
            'warehouseEmployee.warehouse'
        ])
        ->where('position', '!=', 'super admin')
        ->orderBy('created_at', 'desc');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('firstname', 'like', "%$search%")
                ->orWhere('lastname', 'like', "%$search%");
            });
        }

        // Fetch all employees (no pagination)
        $data = $query->get();

        return response()->json([
            'data' => $data,
            'total' => $data->count(),
        ]);
    }
    public function fetchCertianEmployeeWithEmploymentTypeAndDesignation($id)
    {

        // $query = Employee::with([
        //     'userDesignation',
        //     'employmentType',
        //     'branchEmployee.branch',
        //     'warehouseEmployee.warehouse'
        // ])
        // ->where('position', '!=', 'super admin')
        // ->orderBy('created_at', 'desc');

        // if (!empty($search)) {
        //     $query->where(function ($q) use ($search) {
        //         $q->where('firstname', 'like', "%$search%")
        //         ->orWhere('lastname', 'like', "%$search%");
        //     });
        // }

        // // Fetch all employees (no pagination)
        // $data = $query->get();

        // return response()->json([
        //     'data' => $data,
        //     'total' => $data->count(),
        // ]);
         try {
            // Use findOrFail to get the model or automatically throw a 404 exception.
            // We chain the 'with' and 'where' clauses before the final find.
            $employee = Employee::with([
                    'userDesignation',
                    'employmentType',
                    'branchEmployee.branch',
                    'warehouseEmployee.warehouse'
                ])
                ->where('position', '!=', 'super admin') // Prevents fetching super admins
                ->findOrFail($id); // This is the key change!

            // If found, return the employee data with a 200 OK status.
            return response()->json($employee);

        } catch (ModelNotFoundException $e) {

            // If findOrFail fails, it throws an exception. We catch it here.
            // Return a standard 404 Not Found JSON response.
            return response()->json([
                'message' => 'Employee not found.'
            ], 404);
        }
    }


    // public function fetchEmployeeWithEmploymentTypeAndDesignation(Request $request)
    // {
    //     // Search term is still useful, so we keep it.
    //     $search = $request->query('search', '');

    //     // The query builder remains the same.
    //     $query = Employee::with([
    //         'userDesignation',
    //         'employmentType',
    //         'branchEmployee.branch',
    //         'warehouseEmployee.warehouse'
    //     ])
    //     ->where('position', '!=', 'super admin')
    //     ->orderBy('created_at', 'desc');

    //     // The search logic is also kept.
    //     if (!empty($search)) {
    //         $query->where(function ($q) use ($search) {
    //             $q->where('firstname', 'like', "%$search%")
    //             ->orWhere('lastname', 'like', "%$search%")
    //             // This is a good place to uncomment the advanced search now
    //             ->orWhereHas('branchEmployee.branch', function ($branchQuery) use ($search) {
    //                 $branchQuery->where('name', 'like', "%$search%");
    //             })
    //             ->orWhereHas('warehouseEmployee.warehouse', function ($warehouseQuery) use ($search) {
    //                 $warehouseQuery->where('name', 'like', "%$search%");
    //             });
    //         });
    //     }

    //     // --- MODIFICATION ---
    //     // We remove all pagination logic and ALWAYS fetch all results.
    //     $data = $query->get();

    //     // We return the data in the consistent wrapper object that your
    //     // original "fetch all" logic used.
    //     return response()->json([
    //         'data' => $data,
    //         'total' => $data->count(),
    //         'per_page' => $data->count(),
    //         'current_page' => 1,
    //         'last_page' => 1
    //     ]);
    // }

    /**
     * Show the form for creating a new resource.
     */
    // public function searchEmployees(Request $request)
    // {
    //     $keyword = $request->input('keyword');

    //     // Search by firstname or lastname
    //     $employees = Employee::with('employmentType')
    //         ->where('firstname', 'like', "%$keyword%")
    //         ->orWhere('lastname', 'like', "%$keyword%")
    //         ->take(7)
    //         ->get();

    //     // Check if employees are found
    //     if ($employees->isEmpty()) {
    //         // return response()->json([], 200); // Return an empty array if no results
    //         $employees = Employee::orderBy('created_at', 'asc')->with('employmentType')->take(7)->get();
    //     }

    //     return response()->json($employees, 200);
    // }

    public function searchEmployees(Request $request)
    {
        $keyword = $request->input('keyword');

        // Search by firstname or lastname, excluding "super admin"
        $employees = Employee::with('employmentType')
            ->where(function ($query) use ($keyword) {
                $query->where('firstname', 'like', "%$keyword%")
                    ->orWhere('lastname', 'like', "%$keyword%");
            })
            ->where('position', '!=', 'super admin')  // Exclude employees with the role "super admin"
            ->take(7)
            ->get();

        // Check if employees are found
        if ($employees->isEmpty()) {
            // If no results, return the first 7 employees excluding "super admin"
            $employees = Employee::with('employmentType')
                ->where('position', '!=', 'super admin')  // Exclude "super admin" from the fallback
                ->orderBy('created_at', 'asc')
                ->take(7)
                ->get();
        }

        return response()->json($employees, 200);
    }

    public function searchPersonInCharge(Request $request)
    {
        $keyword = $request->input('keyword');

        // Search by firstname or lastname, excluding "super admin"
        $employees = Employee::with('employmentType')
            ->where(function ($query) use ($keyword) {
                $query->where('firstname', 'like', "%$keyword%")
                    ->orWhere('lastname', 'like', "%$keyword%");
            })
            ->take(7)
            ->get();

        // Check if employees are found
        if ($employees->isEmpty()) {
            // If no results, return the first 7 employees excluding "super admin"
            $employees = Employee::with('employmentType')
                ->where('position', '!=', 'super admin')  // Exclude "super admin" from the fallback
                ->orderBy('created_at', 'asc')
                ->take(7)
                ->get();
        }

        return response()->json($employees, 200);
    }

    public function searchEmployeesWithDesignation(Request $request)
    {
        $keyword = $request->input('keyword');

        // Search by firstname or lastname
        $employees = Employee::with('branchEmployee.branch')
            ->where('firstname', 'like', "%$keyword%")
            ->orWhere('lastname', 'like', "%$keyword%")
            ->take(7)
            ->get();

        // Check if employees are found
        if ($employees->isEmpty()) {
            return response()->json([], 200); // Return an empty array if no results
        }

        return response()->json($employees, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validateEmployee = $request->validate([
            'employment_type_id' => 'required|integer',
            'firstname' => 'required|string|max:255',
            'middlename' => 'required|string|max:255',
            'lastname' =>  'required|string|max:255',
            'birthdate' => 'required|date',
            'phone' => 'required|string|max:25',
            'address' => 'required|string|max:255',
            'sex' => 'required|string|in:Male,Female',
            'position' =>  'required|string|max:255',
            'status' =>  'required|string|max:25',
        ]);

        // Create the employee
        $employee = Employee::create($validateEmployee);

        // Retrieve the employee with the same structure as the index method
        $employee = Employee::with('employmentType')
            ->where('id', $employee->id)
            ->first();

        return response()->json([
            'message' => 'Employee successfully created',
            'employee' => $employee,
        ], 201);
    }

    public function updateEmployeeFullname(Request $request, $id)
    {
        $validateEmployee = $request->validate([
            'firstname' => 'required|string',
            'middlename' => 'required|string',
            'lastname' => 'required|string',
        ]);
        $employee = Employee::findOrFail($id);
        $employee->firstname = $validateEmployee['firstname'];
        $employee->middlename = $validateEmployee['middlename'];
        $employee->lastname = $validateEmployee['lastname'];

        $employee->save();

        $employee->load('employmentType');


        return response()->json([
            'message' => 'Employee fullname updated successfully',
            'employee' => $employee
        ], 200);
    }

    public function updateEmployeeEmploymentType(Request $request, $id)
    {
        $validateEmployee = $request->validate([
            'employment_type_id' => 'required|integer',
        ]);
        $employee = Employee::findOrFail($id);
        $employee->employment_type_id = $validateEmployee['employment_type_id'];


        $employee->save();

        $employee->load('employmentType');


        return response()->json([
            'message' => 'Employee fullname updated successfully',
            'employee' => $employee
        ], 200);
    }
    public function updateEmployeeAddress(Request $request, $id)
    {
        $validateEmployee = $request->validate([
            'address' => 'required|string',
        ]);
        $employee = Employee::findOrFail($id);
        $employee->address = $validateEmployee['address'];


        $employee->save();

        $employee->load('employmentType');


        return response()->json([
            'message' => 'Employee fullname updated successfully',
            'employee' => $employee
        ], 200);
    }
    public function updateEmployeeBirthdate(Request $request, $id)
    {
        $validateEmployee = $request->validate([
            'birthdate' => 'required|date',
        ]);
        $employee = Employee::findOrFail($id);
        $employee->birthdate = $validateEmployee['birthdate'];


        $employee->save();

        $employee->load('employmentType');


        return response()->json([
            'message' => 'Employee fullname updated successfully',
            'employee' => $employee
        ], 200);
    }
    public function updateEmployeePhone(Request $request, $id)
    {
        $validateEmployee = $request->validate([
            'phone' => 'required|string',
        ]);
        $employee = Employee::findOrFail($id);
        $employee->phone = $validateEmployee['phone'];


        $employee->save();

        $employee->load('employmentType');


        return response()->json([
            'message' => 'Employee fullname updated successfully',
            'employee' => $employee
        ], 200);
    }

    public function updateEmployeeDesignation(Request $request, $id)
    {
        $validatedData = $request->validate([
            'designation_id' => 'required|integer',
            'designation_type' => 'required',
        ]);

        $employeeType = $validatedData['designation_type'];
        $designationId = $validatedData['designation_id'];
        try{
            if ($employeeType === 'branch') {
                $employee = BranchEmployee::findOrFail($id);
                $employee->branch_id = $designationId;
                $employee->save();

                return response()->json([
                    'message' => 'Employee designation updated successfully!'
                ]
                );
            }
            if ($employeeType === 'warehouse') {
                $employee = WarehouseEmployee::findOrFail($id);
                $employee->warehouse_id = $designationId;
                $employee->save();
            }
        }catch (ModelNotFoundException $e) {
            // 4. Handle the case where the ID was not found in the specified table
            return response()->json([
                'error' => "No employee found with ID {$id} for designation '{$employeeType}'."
            ], 404); // 404 Not Found
        }

    }

    public function updateEmployeeTimeIn(Request $request, $id)
    {
        $validatedData = $request->validate([
            'designation_type' => 'required|string|in:branch,warehouse',
            'time_in' => 'required|string|max:10',
        ]);

        $employee = null;
        $employeeType = $validatedData['designation_type'];

        try{
            if ($employeeType === 'branch') {
                $employee = BranchEmployee::findOrFail($id);
            }

            else if ( $employeeType === 'warehouse') {
                $employee = WarehouseEmployee::findOrFail($id);
            }

            if ($employee) {
                $employee->time_in = $validatedData['time_in'];
                $employee->save();

               return response()->json([
                    'message' => 'Employee time-in updated successfully.',
                    'employee' => $employee
               ]);
            }
        } catch (ModelNotFoundException $e) {
            // 4. Handle the case where the ID was not found in the specified table
            return response()->json([
                'error' => "No employee found with ID {$id} for designation '{$employeeType}'."
            ], 404); // 404 Not Found
        }


        // This part is unlikely to be reached due to validation, but it's good practice
        return response()->json(['error' => 'Invalid designation type provided.'], 400);
    }
    public function updateEmployeeTimeOut(Request $request, $id)
    {
        $validatedData = $request->validate([
            'designation_type' => 'required|string|in:branch,warehouse',
            'time_out' => 'required|string|max:10',
        ]);

        $employee = null;
        $employeeType = $validatedData['designation_type'];

        try{
            if ($employeeType === 'branch') {
                $employee = BranchEmployee::findOrFail($id);
            }

            else if ( $employeeType === 'warehouse') {
                $employee = WarehouseEmployee::findOrFail($id);
            }

            if ($employee) {
                $employee->time_out = $validatedData['time_out'];
                $employee->save();

               return response()->json([
                    'message' => 'Employee time-out updated successfully.',
                    'employee' => $employee
               ]);
            }
        } catch (ModelNotFoundException $e) {
            // 4. Handle the case where the ID was not found in the specified table
            return response()->json([
                'error' => "No employee found with ID {$id} for designation '{$employeeType}'."
            ], 404); // 404 Not Found
        }


        // This part is unlikely to be reached due to validation, but it's good practice
        return response()->json(['error' => 'Invalid designation type provided.'], 400);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        //
    }
}
