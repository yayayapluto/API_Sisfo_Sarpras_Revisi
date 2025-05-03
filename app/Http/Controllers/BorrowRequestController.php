<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\BorrowRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BorrowRequestController extends Controller
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
        $borrowRequestQuery = BorrowRequest::query();

        $validColumns = [
            "return_date_expected",
            "status",
            "user_id",
            "approved_by"
        ];

        $validRelation = [
            "user",
            "approver",
            "borrowDetails",
            "returnRequest"
        ];

        if (!is_null($this->currentUserId)) {
            $borrowRequestQuery->where('user_id', $this->currentUserId);
            $validRelation = array_diff($validRelation, ["user"]);
            $validColumns = array_diff($validColumns, ["user_id"]);
        }

        if (\request()->filled("with")) {
            $relations = explode(",", trim(\request()->with));
            foreach ($relations as $relation) {
                if (in_array($relation, $validRelation)) {
                    $borrowRequestQuery = $borrowRequestQuery->with($relation);
                }
            }
        }

        foreach (request()->except(['page', 'size', 'sortBy', 'sortDir', 'with']) as $key => $value) {
            if (in_array($key, $validColumns)) {
                $borrowRequestQuery->where($key, $value);
            }
        }

        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $borrowRequestQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);
        $borrowRequests = $borrowRequestQuery->simplePaginate($size);

        return response()->json([
            'status' => 200,
            'message' => 'Borrow request list retrieved',
            'data' => $borrowRequests
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
        $borrowRequestQuery = BorrowRequest::query()->with(["user","approver","borrowDetails","returnRequest"]);

        $borrowRequest = null;
        if (is_null($this->currentUserId)) {
            $borrowRequest = $borrowRequestQuery->find($id);
        } else {
            $borrowRequest = $borrowRequestQuery->where("user_id", $this->currentUserId)->first();
        }

        if (is_null($borrowRequest)) {
            return Formatter::apiResponse(404, "Borrow request not found");
        }
        return Formatter::apiResponse(200, "Borrow request found", $borrowRequest);
    }

    public function approve(int $id)
    {
        $borrowRequest = BorrowRequest::query()->find($id);
        if (is_null($borrowRequest)) {
            return Formatter::apiResponse(404, "Borrow request not found");
        }
        if ($borrowRequest->status === "approved") {
            return Formatter::apiResponse(400, "Borrow request already approved");
        }
        $borrowRequest->update([
            "status" => "approved"
        ]);
        return Formatter::apiResponse(200, "Borrow request approved", $borrowRequest->getChanges());
    }

    public function reject(int $id)
    {
        $borrowRequest = BorrowRequest::query()->find($id);
        if (is_null($borrowRequest)) {
            return Formatter::apiResponse(404, "Borrow request not found");
        }
        if ($borrowRequest->status === "rejected") {
            return Formatter::apiResponse(400, "Borrow request already rejected");
        }
        $borrowRequest->update([
            "status" => "rejected"
        ]);
        return Formatter::apiResponse(200, "Borrow request rejected", $borrowRequest->getChanges());
    }
}
