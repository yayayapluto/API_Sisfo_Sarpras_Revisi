<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogActivity extends Model
{
    protected $fillable = [
        "entity",
        "entity_id",
        "type",
        "old_value",
        "new_value",
        "ip_address",
        "user_agent",
        "performed_by"
    ];

    public function performer()
    {
        return $this->belongsTo(User::class, "performer_id");
    }
}
