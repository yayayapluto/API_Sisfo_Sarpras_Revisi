<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\Category;
use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    public function index(): JsonResponse
    {
        $itemQuery = Item::query();
        $validColumns = [
            'name', 'type', 'image_url',
            'qr_image_url', 'category_id'
        ];
        $validRelation = ["category"];

        if (\request()->filled("with")) {
            $relations = explode(",", trim(\request()->with));
            foreach ($relations as $relation) {
                if (in_array($relation, $validRelation)) {
                    $itemQuery = $itemQuery->with($relation);
                }
            }
        }

        if (\request()->filled("search")) {
            $searchTerm = '%' . \request()->search . '%';
            $itemQuery->where(function ($query) use ($searchTerm) {
                $query->where('name', 'LIKE', $searchTerm)
                    ->orWhere('description', 'LIKE', $searchTerm);
            });
        }

        foreach (request()->except(['page', 'size', 'sortBy', 'sortDir', 'search', 'with']) as $key => $value) {
            if (in_array($key, $validColumns)) {
                $itemQuery->where($key, $value);
            }
        }

        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $itemQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);
        $items = $itemQuery->simplePaginate($size);

        return Formatter::apiResponse(200, 'Item list retrieved', $items);
    }

    public function store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            "name" => "required|string|min:3",
            "type" => "required|string",
            "description" => "sometimes|string",
            "image" => "sometimes|image",
            "category" => "required|exists:categories,slug"
        ]);

        if ($validator->fails()) {
            return Formatter::apiResponse(422, "Validation failed", null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $validated["category_id"] = Category::query()->select("id")->where("slug", $validated["category"])->pluck("id")->first();

        if ($request->hasFile("image")) {
            $imageFile = $request->file("image");
            $path = "item-images";
            $fileName = Formatter::makeDash($validated["name"] . " upload " . Carbon::now()->toDateString()) . "." . $imageFile->getClientOriginalExtension();
            $storedPath = $imageFile->storeAs($path, $fileName, "public");
            if (!$storedPath) {
                return Formatter::apiResponse(400, "Cannot upload image, please try again later");
            }
            $validated["image_url"] = url(Storage::url($storedPath));
        }

        $newItem = Item::query()->create($validated);
        return Formatter::apiResponse(200, "Item created", Item::query()->find($newItem->id)->load("category"));
    }

    public function show(int $id)
    {
        $item = Item::query()->with("category")->find($id);
        if (is_null($item)) {
            return Formatter::apiResponse(404, "Item not found");
        }
        return Formatter::apiResponse(200, "Item found", $item);
    }

    public function update(Request $request, int $id)
    {
        $item = Item::query()->find($id);
        if (is_null($item)) {
            return Formatter::apiResponse(404, "Item not found");
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            "name" => "sometimes|string|min:3",
            "type" => "sometimes|string",
            "description" => "sometimes|string",
            "image_url" => "sometimes|url",
            "qr_image_url" => "sometimes|url",
            "category_id" => "sometimes|exists:categories,id"
        ]);

        if ($validator->fails()) {
            return Formatter::apiResponse(422, "Validation failed", null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $item->update($validated);
        return Formatter::apiResponse(200, "Item updated", $item->getChanges());
    }

    public function destroy(int $id)
    {
        $item = Item::find($id);
        if (is_null($item)) {
            return Formatter::apiResponse(404, "Item not found");
        }
        $item->delete();
        return Formatter::apiResponse(200, "Item deleted");
    }
}
