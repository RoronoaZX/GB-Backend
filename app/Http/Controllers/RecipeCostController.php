<?php

namespace App\Http\Controllers;

use App\Models\RecipeCost;
use Illuminate\Http\Request;

class RecipeCostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function fetchRecipeCosts(Request $request, $branchId)
    {
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 3);
        $search = $request->query('search', '');

        // ✅ Step 1: Base Query
        $query = RecipeCost::where('branch_id', $branchId)
                   ->with(['recipe', 'branchRmStock', 'user', 'initialBakerreport', 'rawMaterial'])
                   ->orderBy('created_at', 'desc');

        // ✅ Step 2: Apply search filter (by recipe name or raw material name)
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('recipe', function ($r) use ($search) {
                    $r->where('name', 'like', "%{$search}%");
                });
            });
        }

        // ✅ Step 3: Get data and group by recipe_id
        $grouped = $query->get()
            ->groupBy('recipe_id')
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'recipe_id'      => $first->recipe_id,
                    'recipe_name'    => $first->recipe?->name,
                    'total_cost'     => $group->sum('total_cost'),
                    'user'           => $first->user,
                    'created_at'     => $first->created_at,
                    'kilo'           => $first->initialBakerreport?->kilo ?? null,

                    'items' => $group->map(function ($item) {
                        return [
                            'raw_material_id'    => $item->raw_material_id,
                            'raw_material_name'  => $item->rawMaterial?->name ?? null,
                            'raw_material_code'  => $item->rawMaterial?->code ?? null,
                            'quantity_used'      => $item->quantity_used,
                            'price_per_gram'     => $item->price_per_gram,
                            'total_cost'         => $item->total_cost,
                            'kilo'               => $item->kilo,
                            'status'             => $item->status,

                            'branch_rm_stock'    => $item->branchRmStock ? [
                                'id'                 => $item->branchRmStock->id,
                                'stock_name'         => $item->branchRmStock->rawMaterial->name ?? null,
                                'remaining'          => $item->branchRmStock->remaining ?? null,
                                'price_per_gram'     => $item->branchRmStock->price_per_gram ?? null,
                            ] : null
                            ];
                    })->values()
                ];
            })
            ->values();

            // ✅ Step 4: Manual pagination
            $total = $grouped->count();
            $lastPage = ceil($total / $perPage);
            $paginated = $grouped->slice(($page - 1) * $perPage, $perPage)->values();

            // ✅ Step 5: Build pagination links
            $baseUrl = url()->current();
            $queryParams = $request->except('page'); // keep other filter (like search)
            $queryString = http_build_query($queryParams);

            $links = [
                'first'  => $baseUrl . '?' . $queryString . '&page=1',
                'last'   => $baseUrl . '?' . $queryString . '&page=' . $lastPage,
                'prev'   => $page > 1 ? $baseUrl . '?' . $queryString .'&page=' . ($page - 1) : null,
                'next'   => $page < $lastPage ? $baseUrl . '?' . $queryString . '&page=' . ($page + 1) : null,
            ];

            // ✅ Step 6: Return paginated response
            return response()->json([
                'data'           => $paginated,
                'total'          => $total,
                'per_page'       => $perPage,
                'current_page'   => $perPage,
                'last_page'      => $lastPage,
                'links'          => $links,
            ]);
    }

    // public function fetchRecipeCostss(Request $request, $branchId)
    // {
    //     $page = (int) $request->get('page', 1);
    //     $perPage = (int) $request->get('per_page', 3);
    //     $search = $request->query('search', '');

    //     // ✅ Step 1: Base Query
    //     $query = RecipeCost::where('branch_id', $branchId)
    //         ->with(['recipe', 'branchRmStock', 'user', 'initialBakerreport', 'rawMaterial'])
    //         ->orderBy('created_at', 'desc');

    //     // ✅ Step 2: Apply search filter (by recipe name or raw material name)
    //     if (!empty($search)) {
    //         $query->where(function ($q) use ($search) {
    //             $q->whereHas('recipe', function ($r) use ($search) {
    //                 $r->where('name', 'like', "%{$search}%");
    //             })
    //             ->orWhereHas('rawMaterial', function ($r) use ($search) {
    //                 $r->where('name', 'like', "%{$search}%");
    //             });
    //         });
    //     }

    //     // ✅ Step 3: Get data and group by recipe_id
    //     $grouped = $query->get()
    //         ->groupBy('recipe_id')
    //         ->map(function ($group) {
    //             $first = $group->first();

    //             return [
    //                 'recipe_id' => $first->recipe_id,
    //                 'recipe_name' => $first->recipe?->name,
    //                 'total_cost' => $group->sum('total_cost'),
    //                 'user' => $first->user,
    //                 'created_at' => $first->created_at,
    //                 'kilo' => $first->initialBakerreport?->kilo ?? null,

    //                 'items' => $group->map(function ($item) {
    //                     return [
    //                         'raw_material_id' => $item->raw_material_id,
    //                         'raw_material_name' => $item->rawMaterial?->name ?? null,
    //                         'raw_material_code' => $item->rawMaterial?->code ?? null,
    //                         'quantity_used' => $item->quantity_used,
    //                         'price_per_gram' => $item->price_per_gram,
    //                         'total_cost' => $item->total_cost,
    //                         'kilo' => $item->kilo,
    //                         'status' => $item->status,

    //                         'branch_rm_stock' => $item->branchRmStock ? [
    //                             'id' => $item->branchRmStock->id,
    //                             'stock_name' => $item->branchRmStock->rawMaterial->name ?? null,
    //                             'remaining' => $item->branchRmStock->remaining ?? null,
    //                             'price_per_gram' => $item->branchRmStock->price_per_gram ?? null,
    //                         ] : null
    //                     ];
    //                 })->values()
    //             ];
    //         })
    //         ->values();

    //     // ✅ Step 4: Manual pagination
    //     $total = $grouped->count();
    //     $lastPage = ceil($total / $perPage);
    //     $paginated = $grouped->slice(($page - 1) * $perPage, $perPage)->values();

    //     // ✅ Step 5: Build pagination links
    //     $baseUrl = url()->current();
    //     $queryParams = $request->except('page'); // keep other filters (like search)
    //     $queryString = http_build_query($queryParams);

    //     $links = [
    //         'first' => $baseUrl . '?' . $queryString . '&page=1',
    //         'last'  => $baseUrl . '?' . $queryString . '&page=' . $lastPage,
    //         'prev'  => $page > 1 ? $baseUrl . '?' . $queryString . '&page=' . ($page - 1) : null,
    //         'next'  => $page < $lastPage ? $baseUrl . '?' . $queryString . '&page=' . ($page + 1) : null,
    //     ];

    //     // ✅ Step 6: Return paginated response
    //     return response()->json([
    //         'data' => $paginated,
    //         'total' => $total,
    //         'per_page' => $perPage,
    //         'current_page' => $page,
    //         'last_page' => $lastPage,
    //         'links' => $links,
    //     ]);
    // }

    // public function fetchRecipeCosts(Request $request, $branchId)
    // {
    //     $page = (int) $request->get('page', 1);
    //     $perPage = (int) $request->get('per_page', 5);
    //     $search = $request->query('search', '');

    //     // ✅ Step 1: Base Query
    //     $query = RecipeCost::where('branch_id', $branchId)
    //         ->with(['recipe', 'branchRmStock', 'user', 'initialBakerreport', 'rawMaterial'])
    //         ->orderBy('created_at', 'desc');

    //     // ✅ Step 2: Apply search filter (by recipe name or raw material name)
    //     if (!empty($search)) {
    //         $query->whereHas('recipe', function ($q) use ($search) {
    //             $q->where('name', 'like', "%{$search}%");
    //         })->orWhereHas('rawMaterial', function ($q) use ($search) {
    //             $q->where('name', 'like', "%{$search}%");
    //         });
    //     }

    //     // ✅ Step 3: Get data and group by recipe_id
    //     $grouped = $query->get()
    //         ->groupBy('recipe_id')
    //         ->map(function ($group) {
    //             $first = $group->first();

    //             return [
    //                 'recipe_id' => $first->recipe_id,
    //                 'recipe_name' => $first->recipe?->name,
    //                 'total_cost' => $group->sum('total_cost'),
    //                 'user' => $first->user,
    //                 'created_at' => $first->created_at,
    //                 'kilo' => $first->initialBakerreport?->kilo ?? null,

    //                 'items' => $group->map(function ($item) {
    //                     return [
    //                         'raw_material_id' => $item->raw_material_id,
    //                         'raw_material_name' => $item->rawMaterial?->name ?? null,
    //                         'raw_material_code' => $item->rawMaterial?->code ?? null,
    //                         'quantity_used' => $item->quantity_used,
    //                         'price_per_gram' => $item->price_per_gram,
    //                         'total_cost' => $item->total_cost,
    //                         'kilo' => $item->kilo,
    //                         'status' => $item->status,

    //                         'branch_rm_stock' => $item->branchRmStock ? [
    //                             'id' => $item->branchRmStock->id,
    //                             'stock_name' => $item->branchRmStock->rawMaterial->name ?? null,
    //                             'remaining' => $item->branchRmStock->remaining ?? null,
    //                             'price_per_gram' => $item->branchRmStock->price_per_gram ?? null,
    //                         ] : null
    //                     ];
    //                 })->values()
    //             ];
    //         })
    //         ->values();

    //     // ✅ Step 4: Manual pagination
    //     $total = $grouped->count();
    //     $paginated = $grouped->slice(($page - 1) * $perPage, $perPage)->values();

    //     // ✅ Step 5: Return paginated response
    //     return response()->json([
    //         'data' => $paginated,
    //         'total' => $total,
    //         'per_page' => $perPage,
    //         'current_page' => $page,
    //         'last_page' => ceil($total / $perPage),

    //         // 'meta' => [
    //         //     'current_page' => $page,
    //         //     'per_page' => $perPage,
    //         //     'total' => $total,
    //         //     'last_page' => ceil($total / $perPage),
    //         // ],
    //     ]);
    // }

    // public function fetchRecipeCosts($branchId)
    // {
    //     $recipeCosts = RecipeCost::where('branch_id', $branchId)
    //                         ->with(['recipe', 'branchRmStock', 'user', 'initialBakerreport', 'rawMaterial'])
    //                         ->orderBy('created_at', 'desc')
    //                         ->get()
    //                         ->groupBy('recipe_id')
    //                         ->map(function ($group) {
    //                             $first = $group->first(); // use first item as base

    //                             return [
    //                                 'recipe_id' => $first->recipe_id,
    //                                 'recipe_name' => $first->recipe?->name,
    //                                 'total_cost' => $group->sum('total_cost'),
    //                                 'user' => $first->user,
    //                                 'created_at' => $first->created_at,
    //                                 'kilo' => $first->initialBakerreport?->kilo ?? null,

    //                                 'items' => $group->map(function ($item) {
    //                                     return [
    //                                         'raw_material_id' => $item->raw_material_id,
    //                                         'raw_material_name' => $item->rawMaterial?->name ?? null, // optional relation
    //                                         'raw_material_code' => $item->rawMaterial?->code ?? null, // optional relation
    //                                         'quantity_used' => $item->quantity_used,
    //                                         'price_per_gram' => $item->price_per_gram,
    //                                         'total_cost' => $item->total_cost,
    //                                         'kilo' => $item->kilo,
    //                                         'status' => $item->status,

    //                                         // ✅ Safely access branchRmStock (can be null)
    //                                         'branch_rm_stock' => $item->branchRmStock ? [
    //                                             'id' => $item->branchRmStock->id,
    //                                             'stock_name' => $item->branchRmStock->rawMaterial->name ?? null, // optional relation
    //                                             'remaining' => $item->branchRmStock->remaining ?? null,
    //                                             'price_per_gram' => $item->branchRmStock->price_per_gram ?? null,
    //                                         ] : null
    //                                         ];
    //                                 })->values()

    //                             ];
    //                         })
    //                         ->values();

    //     return response()->json($recipeCosts);
    // }

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
    public function show(RecipeCost $recipeCost)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RecipeCost $recipeCost)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RecipeCost $recipeCost)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RecipeCost $recipeCost)
    {
        //
    }
}
