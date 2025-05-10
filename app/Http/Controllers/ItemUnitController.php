<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\Warehouse;
use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Custom\Formatter;
use Endroid\QrCode\QrCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ItemUnitController extends Controller
{
    public function index(): JsonResponse
    {
        $itemUnitQuery = ItemUnit::query();
        $validColumns = [
            'sku', 'condition', 'acquisition_source',
            'acquisition_date', 'status', 'quantity',
            'item_id', 'warehouse_id', 'created_at'
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
                $query->where('sku', 'LIKE', $searchTerm)
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


        foreach ($itemUnits as $key => $value) {
            $itemUnits[$key]->qr_image_url = url($value->qr_image_url);
        }


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
            "item_id" => "required|integer|exists:items,id",
            "warehouse_id" => "required|integer|exists:warehouses,id"
        ]);

        if ($validator->fails()) {
            return Formatter::apiResponse(422, "Validation failed", null, $validator->errors()->all());
        }

        $validated = $validator->validated();

        DB::beginTransaction();
        try {
            $warehouse = Warehouse::query()->find($request->warehouse_id);
            $item = Item::query()->find($request->item_id);
            $sku = Formatter::makeDash($warehouse->name) . "-" . Formatter::makeDash($item->name) . "-" . ($item->itemUnits->sum('quantity') + 1);

            if ($warehouse->capacity <= 0) {
                DB::rollBack();
                return Formatter::apiResponse(400, "There is no space in this warehouse, please change");
            }

            if ($item->type === "non-consumable") $validated["quantity"] = 1;

            $validated["sku"] = $sku;

            if (!Storage::disk('public')->exists('qr-images')) {
                Storage::disk('public')->makeDirectory('qr-images');
            }

            $renderer = new GDLibRenderer(400);
            // $renderer = new ImageRenderer(
            //     new RendererStyle(400),
            //     new ImagickImageBackEnd()
            // );
            $qrCode = new \BaconQrCode\Writer($renderer);

            $qrCodePath = 'qr-images/' . $sku . '.png';

            $qrValue = url($sku);
            $qrCode->writeFile($qrValue, storage_path('app/public/' . $qrCodePath));

            $validated["qr_image_url"] = Storage::url($qrCodePath); // url

            $newItemUnit = ItemUnit::query()->create($validated);
            $warehouse->update([
                "capacity" => $warehouse->capacity - 1
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return Formatter::apiResponse(500, "Something went wrong", null, $e->getMessage());
        }

        return Formatter::apiResponse(200, "Item unit created", ItemUnit::query()->find($newItemUnit->id)->load(["item", "warehouse"]));
    }

    public function show(string $sku): JsonResponse
    {
        $itemUnit = ItemUnit::query()->with(["item.category", "warehouse"])->where("sku", $sku)->first();
        if (is_null($itemUnit)) {
            return Formatter::apiResponse(404, "Item unit not found");
        }
        $itemUnit->qr_image_url = url($itemUnit->qr_image_url);
        return Formatter::apiResponse(200, "Item unit found", $itemUnit);
    }

    public function update(Request $request, string $sku): JsonResponse
    {
        $itemUnit = ItemUnit::query()->where("sku", $sku)->first();
        if (is_null($itemUnit)) {
            return Formatter::apiResponse(404, "Item unit not found");
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            "sku" => "sometimes|string|unique:item_units,sku," . $itemUnit->id,
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

    public function destroy(string $sku): JsonResponse
    {
        $itemUnit = ItemUnit::query()->where("sku", $sku)->first();
        if (is_null($itemUnit)) {
            return Formatter::apiResponse(404, "Item unit not found");
        }
        $itemUnit->delete();
        return Formatter::apiResponse(200, "Item unit deleted");
    }
}
