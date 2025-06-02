<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\Category;
use Dotenv\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CategoryImport;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $categoryQuery = Category::query();

        $validRelation = [
            "items"
        ];

        if (\request()->filled("columns")) {
            $columns = explode(',', \request()->columns);
            $categoryQuery->select($columns);
        }

        if (\request()->filled("with")) {
            $relations = explode(",", trim(\request()->with));
            foreach ($relations as $relation) {
                if (in_array($relation, $validRelation)) {
                    $categoryQuery = $categoryQuery->with($relation);
                }
            }
        }

        $validColumns = [
            'name', 'description'
        ];

        if (request()->filled('search')) {
            $searchTerm = '%' . request()->search . '%';
            $categoryQuery->where(function ($query) use ($searchTerm) {
                $query->where('name', 'LIKE', $searchTerm);
            });
        }

        foreach (request()->except(['page', 'size', 'sortBy', 'sortDir', 'search',"with"]) as $key => $value) {
            if (in_array($key, $validColumns)) {
                $categoryQuery->where($key, $value);
            }
        }

        $sortBy = in_array(request()->sortBy, $validColumns)
            ? request()->sortBy
            : 'created_at';

        $sortDir = strtolower(request()->sortDir) === 'desc'
            ? 'DESC'
            : 'ASC';

        $categoryQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);

        $categories = $categoryQuery->simplePaginate($size);
        return Formatter::apiResponse(200, "Category list retrieved", $categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            "name" => "required|string|min:5",
            "description" => "sometimes"
        ]);

        if ($validator->fails()) {
            return Formatter::apiResponse(422, "Validation failed", null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $validated["slug"] = Formatter::makeDash($validated["name"]);

        if (Category::query()->where("slug", $validated["slug"])->exists()) {
            return Formatter::apiResponse(400, "Category already exists");
        }

        $newCategory = Category::query()->create($validated);
        return Formatter::apiResponse(200, "Category created", $newCategory);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $slug)
    {
        $category = Category::query()->with("items.category")->where("slug", trim($slug))->first();
        if (is_null($category)) {
            return Formatter::apiResponse(404, "Category not found");
        }

        foreach ($category->items as $item) {
            $item->image_url = url($item->image_url);
        }

        return Formatter::apiResponse(200, "Category found", $category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $slug)
    {
        $category = Category::query()->where("slug", trim($slug))->first();
        if (is_null($category)) {
            return Formatter::apiResponse(404, "Category not found");
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            "name" => "sometimes|string|min:5",
            "description" => "sometimes"
        ]);

        if ($validator->fails()) {
            return Formatter::apiResponse(422, "Validation failed", null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        if (isset($validated["name"])) {
            $validated["slug"] = Formatter::makeDash($validated["name"]);
            if ($slug !== $validated["slug"]) {
                if (Category::query()->where("slug", $validated["slug"])->exists()) {
                    return Formatter::apiResponse(400, "Category already exists");
                }
            }
        }

        $category->update($validated);
        return Formatter::apiResponse(200, "Category updated", Category::query()->find($category->id));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $slug)
    {
        $category = Category::query()->where("slug", trim($slug))->first();
        if (is_null($category)) {
            return Formatter::apiResponse(404, "Category not found");
        }
        $category->delete();
        return Formatter::apiResponse(200, "Category deleted");
    }

    public function importCategories(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv',
        ]);
        try {
            \DB::beginTransaction();
            Excel::import(new CategoryImport, $request->file('file'));
            \DB::commit();
            return Formatter::apiResponse(200, 'Categories imported successfully', []);
        } catch (\Exception $e) {
            \DB::rollBack();
            return Formatter::apiResponse(422, 'Import failed', null, [$e->getMessage()]);
        }
    }
}
