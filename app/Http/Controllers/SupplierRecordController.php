<?php

namespace App\Http\Controllers;

use App\Models\SupplierRecord;
use Illuminate\Http\Request;

class SupplierRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function getSupplierRecords(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 5);
        $search = $request->query('search', '');

        $query = SupplierRecord::with('supplierIngredients.rawMaterials');

        // ✅ Add search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('supplier_name', 'like', "%{$search}%")
                  ->orWhere('statu', 'like', "%{$search}%");
            });
        }

        // ✅ If perPage = 0, return all data without pagination
        if ($perPage == 0) {
            $data = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'data'           => $data,
                'total'          => $data->count(),
                'per_page'       => $data->count(),
                'current_page'   => 1,
                'last_page'      => 1
            ]);
        }

        // ✅ Get paginated data
        $paginated = $query->orderBy('created_at', 'desc')
                           ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($paginated);
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
    public function show(SupplierRecord $supplierRecord)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SupplierRecord $supplierRecord)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SupplierRecord $supplierRecord)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SupplierRecord $supplierRecord)
    {
        //
    }
}
