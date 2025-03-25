<?php

namespace App\Http\Controllers;

use App\Models\BranchEmployee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with('employee')->orderBy('created_at', 'desc')->get();
        // ->take(12)

        $users = $users->map(function($user){
            if ($user->employee) {
                $user->employee_id = $user->employee->id;
                $user->firstname = $user->employee->firstname;
                $user->middlename = $user->employee->middlename;
                $user->lastname = $user->employee->lastname;
                $user->birthdate = $user->employee->birthdate;
                $user->phone = $user->employee->phone;
                $user->address = $user->employee->address;
                $user->position = $user->employee->position;

            }
            unset($user->employee);
            return $user;
        });
        return response()->json($users);
    }

    public function search(Request $request)
    {
        $search = $request->input('keyword'); // Get the search input

        $users = User::with('employee')
            ->when($search !== null, function ($query) use ($search) {
                // Search by attributes in the Employee model
                $query->whereHas('employee', function ($employeeQuery) use ($search) {
                    $employeeQuery->where('firstname', 'like', "%{$search}%")
                                  ->orWhere('lastname', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            // ->take(12)
            ->get();

        // Map the results as in the original function
        $users = $users->map(function($user){
            if ($user->employee) {
                $user->employee_id = $user->employee->id;
                $user->firstname = $user->employee->firstname;
                $user->middlename = $user->employee->middlename;
                $user->lastname = $user->employee->lastname;
                $user->birthdate = $user->employee->birthdate;
                $user->phone = $user->employee->phone;
                $user->address = $user->employee->address;
                $user->position = $user->employee->position;
            }
            unset($user->employee);
            return $user;
        });

        return response()->json($users);
    }

    public function searchUser(Request $request)
    {
        $keyword = $request->input('keyword');

        // Validate the input
        $request->validate([
            'keyword' => 'nullable|string|max:255' // 'nullable' allows the keyword to be empty
        ]);

        if (empty($keyword)) {
            // If the keyword is empty, fetch all users
            $results = User::orderBy('created_at', 'desc')->get();
        } else {
            // Perform the search using a search method or query
            $results = User::where('name', 'LIKE', '%' . $keyword . '%')
                            ->orWhere('email', 'LIKE', '%' . $keyword . '%')
                            ->orderBy('created_at', 'desc')
                            ->get();
        }

        return response()->json($results);
    }
    public function fetchUserById ($userId)
    {
        $user = User::with('branchEmployee.branch')->findOrFail($userId);
        if(!$user) {
            return response()->json(["message" => "User not found"], 404);
        }
        return response()->json($user);
    }


    public function searchUserWithID(Request $request)
    {
        $keyword = $request->input('keyword');
        $branchId = $request->input('branch_id');

        $request->validate([
            'keyword' => 'required|string|max:255',
            'branch_id' => 'required|integer|exist:branches,id'
        ]);

        if (empty($keyword)) {
            // If the keyword is an empty string, fetch all users
            $results = User::where('branch_id', $branchId)
                    ->orderBy('created_at', 'desc')
                    ->get();
        } else {
            // Otherwise, perform the search
            $results = User::where('branch_id', $branchId)
                    ->where(function($query) use ($keyword) {
                        $query->where('name', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('email', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('phone', 'LIKE', '%' . $keyword . '%');
                    })
                    ->orderBy('created_at', 'desc')
                    ->get();
        }

        return response()->json($results);

    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'user_fullname' => 'required|string|max:255',
            'user_address' => 'required|string|max:255',
            'user_birthdate' => 'required|date',
            'user_sex' => 'required|string|in:Male,Female',
            'user_status' => 'required|string|in:Current,Former',
            'user_phone_number' => 'required|string|max:25',
            'user_position' => 'required|string|max:255',

        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'name' => $request->user_fullname,
            'address' => $request->user_address,
            'birthdate' => $request->user_birthdate,
            'sex' => $request->user_sex,
            'status' => $request->user_status,
            'phone' => $request->user_phone_number,
            'role' => $request->user_position,
            'remember_token' => Str::random(60),
        ]);
        return response()->json($user);


    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::findOrFail($id);

        return response()->json($user);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateUser(Request $request, $userId)
{
    // Debugging: Output the userId to check its value
    Log::info('User ID received for update: ' . $userId);

    // Find the user or return a 404 response if not found
    $user = User::find($userId); // Changed to find() for debugging

    // Debugging: Check if user was found
    if (!$user) {
        Log::error('User not found with ID: ' . $userId);
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }

    // Validate the incoming request
    $validatedData = $request->validate([
        'name' => 'required|string|max:255',
        'address' => 'required|string|max:255',
        'birthdate' => 'required|date',
        'sex' => 'required|string|in:Male,Female',
        'status' => 'required|string|in:Current,Former',
        'phone' => 'required|string|max:25',
        'role' => 'required|string|max:255',
        'branch_id' => 'required|integer',
        'time_shift' => 'required|date_format:h:i A',
    ]);

    // Update the User model fields
    $user->name = $validatedData['name'];
    $user->address = $validatedData['address'];
    $user->birthdate = $validatedData['birthdate'];
    $user->sex = $validatedData['sex'];
    $user->status = $validatedData['status'];
    $user->phone = $validatedData['phone'];
    $user->role = $validatedData['role'];

    // Save changes to the User model
    $user->save();

    // Find the associated BranchEmployee or create one if it doesn't exist
    $branchEmployee = BranchEmployee::where('user_id', $userId)->first();
    if (!$branchEmployee) {
        Log::error('BranchEmployee not found for user ID: ' . $userId);
        return response()->json([
            'message' => 'BranchEmployee not found for the specified user'
        ], 404);
    }

    // Update the BranchEmployee model fields
    $branchEmployee->branch_id = $validatedData['branch_id'];
    $branchEmployee->time_shift = $validatedData['time_shift'];

    // Save changes to the BranchEmployee model
    $branchEmployee->save();

    // Return a successful response
    return response()->json([
        'message' => 'User profile and branch employee details updated successfully'
    ]);
}

public function updateEmail(Request $request, $id)
{
    $validatedData = $request->validate([
        'email' => 'required|string|max:255'
    ]);

    $user = User::findOrFail($id);
    $user->email = $validatedData['email'];
    $user->save();

    // /** @var User $user */
    // $user = Auth::user(); // This tells Intelephense that $user is a User instance

    // $user->email = $request->email;
    // $user->save();

    // return response()->json(['message' => 'Email updated successfully!'], 200);
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
