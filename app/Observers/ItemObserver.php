<?php

namespace App\Observers;

use App\Models\Item;
use App\Models\LogActivity;

class ItemObserver
{
    private $performedBy;
    private $userIpAddress;
    private $userAgent;

    public function __construct()
    {
        $this->performedBy = request()->user()->id ?? null;
        $this->userIpAddress = request()->ip();
        $this->userAgent = request()->userAgent();
    }

    /**
     * Handle the Item "created" event.
     */
    public function created(Item $item): void
    {
        LogActivity::query()->create([
            "entity" => "Item",
            "entity_id" => $item->id,
            "type" => "create",
            "old_value" => null,
            "new_value" => $item->toJson(),
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }

    /**
     * Handle the Item "updated" event.
     */
    public function updated(Item $item): void
    {
        $oldItem = $item->getOriginal();
        LogActivity::query()->create([
            "entity" => "Item",
            "entity_id" => $item->id,
            "type" => "update",
            "old_value" => json_encode($oldItem),
            "new_value" => $item->toJson(),
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }

    /**
     * Handle the Item "deleted" event.
     */
    public function deleted(Item $item): void
    {
        LogActivity::query()->create([
            "entity" => "Item",
            "entity_id" => $item->id,
            "type" => "delete",
            "old_value" => $item->toJson(),
            "new_value" => null,
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }

    /**
     * Handle the Item "restored" event.
     */
    public function restored(Item $item): void
    {
        LogActivity::query()->create([
            "entity" => "Item",
            "entity_id" => $item->id,
            "type" => "restore",
            "old_value" => null,
            "new_value" => $item->toJson(),
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }

    /**
     * Handle the Item "force deleted" event.
     */
    public function forceDeleted(Item $item): void
    {
        LogActivity::query()->create([
            "entity" => "Item",
            "entity_id" => $item->id,
            "type" => "force_delete",
            "old_value" => $item->toJson(),
            "new_value" => null,
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }
}
