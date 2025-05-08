<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use function PHPUnit\Framework\isEmpty;

class WarehouseController extends Controller
{
    public function index(): JsonResponse
    {
        $warehouseQuery = Warehouse::query();

        $validColumns = [
            'id', 'name', 'location', 'capacity'
        ];

        $validRelation = [
            "itemUnits"
        ];

        if (\request()->filled("with")) {
            $warehouseQuery = $warehouseQuery->with(\request()->with);
        }

        if (request()->filled('search')) {
            $searchTerm = '%' . request()->search . '%';
            $warehouseQuery->where(function ($query) use ($searchTerm) {
                $query->where('name', 'LIKE', $searchTerm)
                    ->orWhere('location', 'LIKE', $searchTerm)
                    ->orWhere('capacity', 'LIKE', $searchTerm);
            });
        }

        foreach (request()->except(['page', 'size', 'sortBy', 'sortDir', 'search', "with", "with"]) as $key => $value) {
            if (in_array($key, $validColumns) && !empty($key)) {
                $warehouseQuery->where($key, $value);
            }
        }

        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $warehouseQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);
        $warehouses = $warehouseQuery->simplePaginate($size);

        return Formatter::apiResponse(200, 'Warehouse list retrieved', $warehouses);
    }

    public function store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            "name" => "required|string|min:3",
            "location" => "required|string",
            "capacity" => "required|integer"
        ]);

        if ($validator->fails()) {
            return Formatter::apiResponse(422, "Validation failed", null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $newWarehouse = Warehouse::query()->create($validated);
        return Formatter::apiResponse(200, "Warehouse created", $newWarehouse);
    }

    public function show(int $id)
    {
        $warehouse = Warehouse::query()->with("itemUnits.item", "itemUnits.warehouse")->find($id);
        if (is_null($warehouse)) {
            return Formatter::apiResponse(404, "Warehouse not found");
        }
        return Formatter::apiResponse(200, "Warehouse found", $warehouse);
    }

    public function update(Request $request, int $id)
    {
        $warehouse = Warehouse::query()->find($id);
        if (is_null($warehouse)) {
            return Formatter::apiResponse(404, "Warehouse not found");
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            "name" => "sometimes|string|min:3",
            "location" => "sometimes|string",
            "capacity" => "sometimes|integer"
        ]);

        if ($validator->fails()) {
            return Formatter::apiResponse(422, "Validation failed", null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $warehouse->update($validated);
        return Formatter::apiResponse(200, "Warehouse updated", Warehouse::query()->find($warehouse->id));
    }

    public function destroy(int $id)
    {
        $warehouse = Warehouse::find($id);
        if (is_null($warehouse)) {
            return Formatter::apiResponse(404, "Warehouse not found");
        }
        $warehouse->delete();
        return Formatter::apiResponse(200, "Warehouse deleted");
    }
}
