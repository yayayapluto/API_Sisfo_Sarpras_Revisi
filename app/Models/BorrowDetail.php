<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BorrowDetail extends Model
{
    protected $fillable = [
        "quantity",
        "borrow_request_id",
        "item_unit_id"
    ];

    public function borrowRequest()
    {
        return $this->belongsTo(BorrowRequest::class);
    }

    public function itemUnit()
    {
        return $this->belongsTo(ItemUnit::class);
    }
}
