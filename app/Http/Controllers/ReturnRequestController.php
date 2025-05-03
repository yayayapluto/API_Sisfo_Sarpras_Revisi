<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;
use function PHPUnit\Framework\isNull;

class ReturnRequestController extends Controller
{
    private $currentUserId;

    public function __construct()
    {
        $currentUser = \request()->user;
        if ($currentUser->role === "user") {
            $this->currentUserId = $currentUser->id;
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $returnRequestQuery = ReturnRequest::query();

        if (!is_null($this->currentUserId)) {
            $returnRequestQuery->join("borrow_requests", "return_requests.borrow_request_id", "=", "borrow_requests.id")->where("borrow_requests.user_id", $this->currentUserId);
        }

        $validColumns = [
            "status",
            "borrow_request_id"
        ];

        $validRelation = [
            "borrowRequest","returnDetails"
        ];

        if (\request()->filled("with")) {
            $relations = explode(",", trim(\request()->with));
            foreach ($relations as $relation) {
                if (in_array($relation, $validRelation)) {
                    $returnRequestQuery = $returnRequestQuery->with($relation);
                }
            }
        }

        foreach (request()->except(['page', 'size', 'sortBy', 'sortDir', 'with']) as $key => $value) {
            if (in_array($key, $validColumns)) {
                $returnRequestQuery->where($key, $value);
            }
        }

        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $returnRequestQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);
        $returnRequests = $returnRequestQuery->simplePaginate($size);

        return response()->json([
            'status' => 200,
            'message' => 'Return request list retrieved',
            'data' => $returnRequests
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // nanti
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $returnRequestQuery = ReturnRequest::query()->with(["borrowRequest","returnDetails"]);

        if (!is_null($this->currentUserId)) {
            $returnRequestQuery->join("borrow_requests", "return_requests.borrow_request_id", "=", "borrow_requests.id")->where("borrow_requests.user_id", $this->currentUserId);
        }

        $returnRequest = $returnRequestQuery->find($id);

        if (is_null($returnRequest)) {
            return Formatter::apiResponse(404, "Return request not found");
        }
        return Formatter::apiResponse(200, "Return request found", $returnRequest);
    }

    public function approve(int $id)
    {
        $returnRequest = ReturnRequest::query()->find($id);
        if (is_null($returnRequest)) {
            return Formatter::apiResponse(404, "Request request not found");
        }
        if ($returnRequest->status === "approved") {
            return Formatter::apiResponse(400, "Request request already approved");
        }
        $returnRequest->update([
            "status" => "approved"
        ]);
        return Formatter::apiResponse(200, "Return request approved");
    }

    public function reject(int $id)
    {
        $returnRequest = ReturnRequest::query()->find($id);
        if (is_null($returnRequest)) {
            return Formatter::apiResponse(404, "Request request not found");
        }
        if ($returnRequest->status === "rejected") {
            return Formatter::apiResponse(400, "Request request already rejected");
        }
        $returnRequest->update([
            "status" => "rejected"
        ]);
        return Formatter::apiResponse(200, "Return request approved");
    }
}
