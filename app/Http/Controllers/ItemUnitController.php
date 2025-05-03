<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Custom\Formatter;

class ItemUnitController extends Controller
{
    public function index(): JsonResponse
    {
        $itemUnitQuery = ItemUnit::query();
        $validColumns = [
            'condition', 'acquisition_source',
            'acquisition_date', 'status', 'quantity',
            'item_id', 'warehouse_id'
        ];
        $validRelation = ["item", "warehouse"];

        if (\request()->filled("with")) {
            $relations = explode(",", trim(\request()->with));
            foreach ($relations as $relation) {
                if (in_array($relation, $validRelation)) {
                    $itemUnitQuery = $itemUnitQuery->with($relation);
                }
            }
        }

        if (\request()->filled("search")) {
            $searchTerm = '%' . \request()->search . '%';
            $itemUnitQuery->where(function ($query) use ($searchTerm) {
                $query->where('slug', 'LIKE', $searchTerm)
                    ->orWhere('condition', 'LIKE', $searchTerm)
                    ->orWhere('notes', 'LIKE', $searchTerm)
                    ->orWhere('acquisition_source', 'LIKE', $searchTerm)
                    ->orWhere('acquisition_notes', 'LIKE', $searchTerm);
            });
        }

        foreach (request()->except(['page', 'size', 'sortBy', 'sortDir', 'search', 'with']) as $key => $value) {
            if (in_array($key, $validColumns)) {
                if ($key === "acquisition_date") {
                    $itemUnitQuery->whereDate($key, $value);
                } else {
                    $itemUnitQuery->where($key, $value);
                }
            }
        }

        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $itemUnitQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);
        $itemUnits = $itemUnitQuery->simplePaginate($size);

        return Formatter::apiResponse(200, 'Item unit list retrieved', $itemUnits);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            "condition" => "required|string",
            "notes" => "sometimes|string",
            "acquisition_source" => "required|string",
            "acquisition_date" => "required|date",
            "acquisition_notes" => "sometimes|string",
            "quantity" => "required|integer|min:1",
            "item" => "required|exists:items,id",
            "warehouse" => "required|exists:warehouses,id"
        ]);

        if ($validator->fails()) {
            return Formatter::apiResponse(422, "Validation failed", null, $validator->errors()->all());
        }

        $validated = $validator->validated();

        $warehouse = Warehouse::query()->find($request->warehouse);
        $item = Item::query()->find($request->item);
        $slug = Formatter::makeDash($warehouse->name) . "-" . Formatter::makeDash($item->name) . "-" . $item->itemUnits->sum('quantity') + 1;

        if ($warehouse->quantity <= 0) {
            return Formatter::apiResponse(400, "There is no space in this warehouse, pls change");
        }

        if ($item->type === "non-consumable") $validated["quantity"] = 1;

        $validated["warehouse_id"] = $warehouse->id;
        $validated["item_id"] = $item->id;
        $validated["slug"] = $slug;
        $validated["qr_image_url"] = "will be generated";

        $newItemUnit = ItemUnit::query()->create($validated);
        return Formatter::apiResponse(200, "Item unit created", ItemUnit::query()->find($newItemUnit->id)->load(["item", "warehouse"]));
    }

    public function show(string $slug): JsonResponse
    {
        $itemUnit = ItemUnit::query()->with(["item", "warehouse"])->where("slug", $slug)->first();
        if (is_null($itemUnit)) {
            return Formatter::apiResponse(404, "Item unit not found");
        }
        return Formatter::apiResponse(200, "Item unit found", $itemUnit);
    }

    public function update(Request $request, string $slug): JsonResponse
    {
        $itemUnit = ItemUnit::query()->where("slug", $slug)->first();
        if (is_null($itemUnit)) {
            return Formatter::apiResponse(404, "Item unit not found");
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            "slug" => "sometimes|string|unique:item_units,slug," . $itemUnit->id,
            "condition" => "sometimes|string",
            "notes" => "sometimes|string",
            "acquisition_source" => "sometimes|string",
            "acquisition_date" => "sometimes|date",
            "acquisition_notes" => "sometimes|string",
            "status" => "sometimes|in:available,borrowed,unknown",
            "quantity" => "sometimes|integer|min:1",
            "qr_image_url" => "sometimes|url",
            "item_id" => "sometimes|exists:items,id",
            "warehouse_id" => "sometimes|exists:warehouses,id"
        ]);

        if ($validator->fails()) {
            return Formatter::apiResponse(422, "Validation failed", null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $itemUnit->update($validated);
        return Formatter::apiResponse(200, "Item unit updated", $itemUnit->getChanges());
    }

    public function destroy(string $slug): JsonResponse
    {
        $itemUnit = ItemUnit::query()->where("slug", $slug)->first();
        if (is_null($itemUnit)) {
            return Formatter::apiResponse(404, "Item unit not found");
        }
        $itemUnit->delete();
        return Formatter::apiResponse(200, "Item unit deleted");
    }
}
