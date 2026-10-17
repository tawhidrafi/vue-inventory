<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $query = Warehouse::query()
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                        ->orWhere('address', 'like', "%{$s}%");
                });
            })
            ->orderBy('name');

        if ($request->boolean('all')) {
            return WarehouseResource::collection($query->get());
        }

        return WarehouseResource::collection(
            $query->paginate($request->integer('per_page', 10))
        );
    }

    public function store(WarehouseRequest $request)
    {
        return new WarehouseResource(Warehouse::create($request->validated()));
    }

    public function show(Warehouse $warehouse)
    {
        return new WarehouseResource($warehouse);
    }

    public function update(WarehouseRequest $request, Warehouse $warehouse)
    {
        $warehouse->update($request->validated());

        return new WarehouseResource($warehouse);
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->stocks()->where('quantity', '!=', 0)->exists()) {
            return response()->json([
                'message' => 'Cannot delete a warehouse that still has stock.',
            ], 422);
        }

        $warehouse->delete(); // soft delete

        return response()->noContent();
    }
}
