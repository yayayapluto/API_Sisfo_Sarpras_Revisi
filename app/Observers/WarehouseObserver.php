<?php

namespace App\Observers;

use App\Models\Warehouse;
use App\Models\LogActivity;

class WarehouseObserver
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
     * Handle the Warehouse "created" event.
     */
    public function created(Warehouse $warehouse): void
    {
        LogActivity::query()->create([
            "entity" => "Warehouse",
            "entity_id" => $warehouse->id,
            "type" => "create",
            "old_value" => null,
            "new_value" => $warehouse->toJson(),
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }

    /**
     * Handle the Warehouse "updated" event.
     */
    public function updated(Warehouse $warehouse): void
    {
        $oldWarehouse = $warehouse->getOriginal();
        LogActivity::query()->create([
            "entity" => "Warehouse",
            "entity_id" => $warehouse->id,
            "type" => "update",
            "old_value" => json_encode($oldWarehouse),
            "new_value" => $warehouse->toJson(),
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }

    /**
     * Handle the Warehouse "deleted" event.
     */
    public function deleted(Warehouse $warehouse): void
    {
        LogActivity::query()->create([
            "entity" => "Warehouse",
            "entity_id" => $warehouse->id,
            "type" => "delete",
            "old_value" => $warehouse->toJson(),
            "new_value" => null,
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }

    /**
     * Handle the Warehouse "restored" event.
     */
    public function restored(Warehouse $warehouse): void
    {
        LogActivity::query()->create([
            "entity" => "Warehouse",
            "entity_id" => $warehouse->id,
            "type" => "restore",
            "old_value" => null,
            "new_value" => $warehouse->toJson(),
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }

    /**
     * Handle the Warehouse "force deleted" event.
     */
    public function forceDeleted(Warehouse $warehouse): void
    {
        LogActivity::query()->create([
            "entity" => "Warehouse",
            "entity_id" => $warehouse->id,
            "type" => "force_delete",
            "old_value" => $warehouse->toJson(),
            "new_value" => null,
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }
}
