<?php

namespace App\Observers;

use App\Models\ItemUnit;
use App\Models\LogActivity;

class ItemUnitObserver
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
     * Handle the ItemUnit "created" event.
     */
    public function created(ItemUnit $itemUnit): void
    {
        LogActivity::query()->create([
            "entity" => "ItemUnit",
            "entity_id" => $itemUnit->id,
            "type" => "create",
            "old_value" => null,
            "new_value" => $itemUnit->toJson(),
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }

    /**
     * Handle the ItemUnit "updated" event.
     */
    public function updated(ItemUnit $itemUnit): void
    {
        $oldItemUnit = $itemUnit->getOriginal();
        LogActivity::query()->create([
            "entity" => "ItemUnit",
            "entity_id" => $itemUnit->id,
            "type" => "update",
            "old_value" => json_encode($oldItemUnit),
            "new_value" => $itemUnit->toJson(),
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }

    /**
     * Handle the ItemUnit "deleted" event.
     */
    public function deleted(ItemUnit $itemUnit): void
    {
        LogActivity::query()->create([
            "entity" => "ItemUnit",
            "entity_id" => $itemUnit->id,
            "type" => "delete",
            "old_value" => $itemUnit->toJson(),
            "new_value" => null,
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }

    /**
     * Handle the ItemUnit "restored" event.
     */
    public function restored(ItemUnit $itemUnit): void
    {
        LogActivity::query()->create([
            "entity" => "ItemUnit",
            "entity_id" => $itemUnit->id,
            "type" => "restore",
            "old_value" => null,
            "new_value" => $itemUnit->toJson(),
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }

    /**
     * Handle the ItemUnit "force deleted" event.
     */
    public function forceDeleted(ItemUnit $itemUnit): void
    {
        LogActivity::query()->create([
            "entity" => "ItemUnit",
            "entity_id" => $itemUnit->id,
            "type" => "force_delete",
            "old_value" => $itemUnit->toJson(),
            "new_value" => null,
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }
}
