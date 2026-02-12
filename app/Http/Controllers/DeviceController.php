<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Device;
use App\Models\Employee; // Assuming your model for the users is User
use App\Models\Warehouse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $devices = Device::latest()->take(10)->get()->map(function ($device) {
                    $device->reference = $device->designation === 'branch'
                        ? Branch::find($device->reference_id)
                        : Warehouse::find($device->reference_id);
                    return $device;
        });

        return response()->json($devices);
    }

    public function checkDevice(Request $request) {
        $uuid    = $request->input('uuid');
        $device  = Device::where('uuid', $uuid)->first();
        if ($device) {
            return response()->json(['authorized' => true]);
        }
        return response()->json(['authorized' => false], 401);
    }


    public function store(Request $request)
    {
        $validateData = $request->validate([
            'reference_id'   => 'required',
            'uuid'           => "required|unique:devices",
            'name'           => "required|string|max:255",
            'model'          => "required|string|max:255",
            'os_version'     => "required|string|max:255",
            'designation'    => "required|string|max:255"
        ]);

        $device = Device::create($validateData);

        return response()->json([
            'message'    => 'Device successfully created',
            'device'     => $device,
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $device = Device::find($id);
        if (!$device) {
            return response()->json([
                'message' => 'Device not found'
            ]);
        }
        $device->update($request->all());
        $updated_device = $device->fresh();
        return response()->json($updated_device);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $device = Device::find($id);
        if (!$device) {
            return response()->json([
                'message' => 'Device not found'
            ]);
        }
        $device->delete();
        return response()->json([
            'message' => 'Device deleted successfully'
        ]);
    }
}
