<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\LogActivity;

class CategoryObserver
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
     * Handle the Category "created" event.
     */
    public function created(Category $category): void
    {
        LogActivity::query()->create([
            "entity" => "Category",
            "entity_id" => $category->id,
            "type" => "create",
            "old_value" => null,
            "new_value" => $category->toJson(),
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }

    /**
     * Handle the Category "updated" event.
     */
    public function updated(Category $category): void
    {
        $oldCategory = $category->getOriginal();
        LogActivity::query()->create([
            "entity" => "Category",
            "entity_id" => $category->id,
            "type" => "update",
            "old_value" => json_encode($oldCategory),
            "new_value" => $category->toJson(),
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }

    /**
     * Handle the Category "deleted" event.
     */
    public function deleted(Category $category): void
    {
        LogActivity::query()->create([
            "entity" => "Category",
            "entity_id" => $category->id,
            "type" => "delete",
            "old_value" => $category->toJson(),
            "new_value" => null,
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }

    /**
     * Handle the Category "restored" event.
     */
    public function restored(Category $category): void
    {
        LogActivity::query()->create([
            "entity" => "Category",
            "entity_id" => $category->id,
            "type" => "restore",
            "old_value" => null,
            "new_value" => $category->toJson(),
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }

    /**
     * Handle the Category "force deleted" event.
     */
    public function forceDeleted(Category $category): void
    {
        LogActivity::query()->create([
            "entity" => "Category",
            "entity_id" => $category->id,
            "type" => "force_delete",
            "old_value" => $category->toJson(),
            "new_value" => null,
            "ip_address" => $this->userIpAddress,
            "user_agent" => $this->userAgent,
            "performed_by" => $this->performedBy
        ]);
    }
}
