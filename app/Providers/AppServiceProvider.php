<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\User;
use App\Models\Warehouse;
use App\Observers\CategoryObserver;
use App\Observers\ItemObserver;
use App\Observers\ItemUnitObserver;
use App\Observers\UserObserver;
use App\Observers\WarehouseObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Category::observe(CategoryObserver::class);
        Item::observe(ItemObserver::class);
        ItemUnit::observe(ItemUnitObserver::class);
        User::observe(UserObserver::class);
        Warehouse::observe(WarehouseObserver::class);
    }
}
