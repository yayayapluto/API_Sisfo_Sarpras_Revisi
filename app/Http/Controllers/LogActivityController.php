<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Custom\Formatter;

class LogActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $logQuery = LogActivity::query()->with("performer");
        $validColumns = [
            'entity', 'type', 'ip_address', 'user_agent', 'performed_by'
        ];
        $validRelation = ["performer"];

        if ($request->filled("with")) {
            $relations = explode(",", trim($request->with));
            foreach ($relations as $relation) {
                if (in_array($relation, $validRelation)) {
                    $logQuery = $logQuery->with($relation);
                }
            }
        }

        if ($request->filled("search")) {
            $searchTerm = '%' . $request->search . '%';
            $logQuery->where(function ($query) use ($searchTerm) {
                $query->where('entity', 'LIKE', $searchTerm)
                    ->orWhere('type', 'LIKE', $searchTerm)
                    ->orWhere('ip_address', 'LIKE', $searchTerm)
                    ->orWhere('user_agent', 'LIKE', $searchTerm);
            });
        }

        foreach ($request->except(['page', 'size', 'sortBy', 'sortDir', 'search', 'with']) as $key => $value) {
            if (in_array($key, $validColumns)) {
                $logQuery->where($key, $value);
            }
        }

        $sortBy = in_array($request->sortBy, $validColumns) ? $request->sortBy : 'created_at';
        $sortDir = strtolower($request->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $logQuery->orderBy($sortBy, $sortDir);

        $size = min(max($request->size ?? 10, 1), 100);
        $logs = $logQuery->simplePaginate($size);

        return Formatter::apiResponse(200, 'Log activity list retrieved', $logs);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $logActivity = LogActivity::query()->with("performer")->find($id);
        if (is_null($logActivity)) {
            return Formatter::apiResponse(404, "Log activity not found");
        }
        return Formatter::apiResponse(200, "Log activity found", $logActivity);
    }
}
