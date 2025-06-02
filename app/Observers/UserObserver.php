<?php

namespace App\Observers;

use App\Models\User;
use App\Models\LogActivity;
use Illuminate\Support\Facades\Auth;

class UserObserver
{
    private $performedBy;
    private $userIpAddress;
    private $userAgent;

    public function __construct()
    {
        $this->performedBy = Auth::guard("sanctum")->user()->id ?? null;
        $this->userIpAddress = request()->ip();
        $this->userAgent = request()->userAgent();
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        LogActivity::query()->create([
            "entity" => "User",
            "entity_id" => $user->id,
            "type" => "create",
            "old_value" => null,
            "new_value" => $user->toJson(),
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $oldUser = $user->getOriginal();
        LogActivity::query()->create([
            "entity" => "User",
            "entity_id" => $user->id,
            "type" => "update",
            "old_value" => json_encode($oldUser),
            "new_value" => $user->toJson(),
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        LogActivity::query()->create([
            "entity" => "User",
            "entity_id" => $user->id,
            "type" => "delete",
            "old_value" => $user->toJson(),
            "new_value" => null,
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        LogActivity::query()->create([
            "entity" => "User",
            "entity_id" => $user->id,
            "type" => "restore",
            "old_value" => null,
            "new_value" => $user->toJson(),
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        LogActivity::query()->create([
            "entity" => "User",
            "entity_id" => $user->id,
            "type" => "force_delete",
            "old_value" => $user->toJson(),
            "new_value" => null,
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }
}
