<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BorrowRequest extends Model
{
    /** @use HasFactory<\Database\Factories\BorrowRequestFactory> */
    use HasFactory;

    protected $fillable = [
        "return_date_expected",
        "status",
        "notes",
        "user_id",
        "handled_by"
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, "handled_by");
    }

    public function borrowDetails()
    {
        return $this->hasMany(BorrowDetail::class);
    }

    public function returnRequest()
    {
        return $this->hasOne(ReturnRequest::class);
    }
}
