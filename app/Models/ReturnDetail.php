<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnDetail extends Model
{
    protected $fillable = [
        "condition",
        "item_unit_id",
        "return_request_id"
    ];

    public function itemUnit()
    {
        return $this->belongsTo(ItemUnit::class);
    }

    public function returnRequest()
    {
        return $this->belongsTo(ReturnRequest::class, "return_request_id");
    }
}
