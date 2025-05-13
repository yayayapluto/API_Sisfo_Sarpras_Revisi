<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\BorrowDetail;
use App\Models\BorrowRequest;
use App\Models\ReturnDetail;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use function PHPUnit\Framework\isNull;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReturnRequestExport;

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
            "borrowRequest","returnDetails","handler"
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
                $returnRequestQuery->where($key, "return_requests." .$value);
            }
        }

        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'return_requests.created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $returnRequestQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);
        $returnRequests = $returnRequestQuery->simplePaginate($size);

        return Formatter::apiResponse(200, "Return request list retrieved", $returnRequests);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            "borrow_request_id" => "required|integer|exists:borrow_requests,id",
            "notes" => "sometimes|string"
        ]);

        if ($validator->fails()) {
            return Formatter::apiResponse(422, "Validation failed", null, $validator->errors()->all());
        }

        $validated = $validator->validated();

        DB::beginTransaction();
        try {
            $borrowRequest = BorrowRequest::query()->find($validated["borrow_request_id"]);
            if ($borrowRequest->status !== "approved") {
                DB::rollBack();
                return Formatter::apiResponse(400, "why yo return pending/rejected borrow request LOL");
            }

            if (ReturnRequest::query()->where("borrow_request_id", $validated["borrow_request_id"])->where("status", "pending")->exists()) {
                DB::rollBack();
                return Formatter::apiResponse(400, "Please wait for your previous return request checked by admin");
            }

            if (!BorrowRequest::query()->where("user_id", $this->currentUserId)->find($validated["borrow_request_id"])) {
                DB::rollBack();
                return Formatter::apiResponse(400, "Tht borrow request not even yours");
            }


            $newReturnRequest = ReturnRequest::query()->create($validator->validated());

            $borrowDetails = $borrowRequest->borrowDetails;
            foreach ($borrowDetails as $borrowDetail) {
                ReturnDetail::query()->create([
                    "item_unit_id" => $borrowDetail->item_unit_id,
                    "return_request_id" => $newReturnRequest->id
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return Formatter::apiResponse(500, "Something wrong", null, $e->getMessage());
        }

        return Formatter::apiResponse(200, "Return request sent, please wait for admin approval", $newReturnRequest->load("returnDetails"));
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $returnRequestQuery = ReturnRequest::query()->with(["borrowRequest.borrowDetails.itemUnit.item","returnDetails.itemUnit.item"]);

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
            return Formatter::apiResponse(404, "Return request not found");
        }

        $borrowRequest = BorrowRequest::query()->find($returnRequest->borrowRequest->id);
        $borrowDetails = $borrowRequest->borrowDetails;
        foreach ($borrowDetails as $borrowDetail) {
            $itemUnit = $borrowDetail->itemUnit;
            if ($itemUnit->item->type === 'consumable') {
                $itemUnit->quantity += $borrowDetail->quantity;
                $itemUnit->status = $itemUnit->quantity > 0 ? 'available' : 'unavailable';
                $itemUnit->current_location = $itemUnit->warehouse->location;
                $itemUnit->save();
            } else {
                $itemUnit->update([
                    "status" => "available",
                    "current_location" => $itemUnit->warehouse->location
                ]);
            }
        }

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
            return Formatter::apiResponse(404, "Return request not found");
        }

        $borrowRequest = BorrowRequest::query()->find($returnRequest->borrowRequest->id);
        $borrowDetails = $borrowRequest->borrowDetails;
        foreach ($borrowDetails as $borrowDetail) {
            $borrowDetail->status = "unknown";
        }

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

    public function exportPdf(Request $request)
    {
        $start = $request->query('start');
        $end = $request->query('end');
        if (!$start || !$end) {
            return Formatter::apiResponse(422, 'Start and end date are required');
        }
        return Excel::download(new ReturnRequestExport($start, $end), 'return_requests.pdf', \Maatwebsite\Excel\Excel::DOMPDF);
    }
}
