<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Warehouse;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Models\BorrowRequest;
use App\Models\ReturnRequest;
use App\Models\BorrowDetail;
use Illuminate\Support\Facades\DB;
use App\Custom\Formatter;

class DashboardController extends Controller
{
    public function entityCounts()
    {
        return Formatter::apiResponse(200, 'Entity counts', [
            'users' => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'warehouses' => Warehouse::count(),
            'categories' => Category::count(),
            'items' => Item::count(),
            'item_units' => ItemUnit::count(),
            'borrow_requests' => BorrowRequest::count(),
            'return_requests' => ReturnRequest::count(),
            'consumable_items' => Item::where('type', 'consumable')->count(),
            'non_consumable_items' => Item::where('type', 'non-consumable')->count(),
        ]);
    }

    public function stockStats()
    {
        $lowStock = ItemUnit::where('quantity', '<', 5)->with('item')->get();
        $outOfStock = ItemUnit::where('status', 'unavailable')->with('item')->get();
        $stockByWarehouse = Warehouse::with(['itemUnits' => function($q){ $q->select('warehouse_id', DB::raw('SUM(quantity) as total'))->groupBy('warehouse_id'); }])->get()->map(function($w){
            return [
                'warehouse' => $w->name,
                'total' => $w->itemUnits->sum('quantity')
            ];
        });
        return Formatter::apiResponse(200, 'Stock stats', [
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'stock_by_warehouse' => $stockByWarehouse,
        ]);
    }

    public function borrowReturnStats()
    {
        $borrowOverTime = BorrowRequest::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))->groupBy('date')->orderBy('date')->get();
        $returnOverTime = ReturnRequest::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))->groupBy('date')->orderBy('date')->get();
        $topBorrowed = BorrowDetail::select('item_unit_id', DB::raw('count(*) as total'))->groupBy('item_unit_id')->orderByDesc('total')->with('itemUnit.item')->limit(5)->get();
        $currentBorrowed = ItemUnit::where('status', 'borrowed')->with('item')->get();
        $borrowed = BorrowRequest::where('status', 'approved')->count();
        $returned = ReturnRequest::where('status', 'approved')->count();
        return Formatter::apiResponse(200, 'Borrow/Return stats', [
            'borrow_over_time' => $borrowOverTime,
            'return_over_time' => $returnOverTime,
            'top_borrowed' => $topBorrowed,
            'current_borrowed' => $currentBorrowed,
            'borrowed_vs_returned' => [ 'borrowed' => $borrowed, 'returned' => $returned ],
        ]);
    }

    public function userActivity()
    {
        $mostActive = BorrowRequest::select('user_id', DB::raw('count(*) as total'))->groupBy('user_id')->orderByDesc('total')->with('user')->limit(5)->get();
        $recentBorrow = BorrowRequest::with('user')->orderByDesc('created_at')->limit(5)->get();
        $recentReturn = ReturnRequest::with('borrowRequest.user')->orderByDesc('created_at')->limit(5)->get();
        return Formatter::apiResponse(200, 'User activity', [
            'most_active_users' => $mostActive,
            'recent_borrow_requests' => $recentBorrow,
            'recent_return_requests' => $recentReturn,
        ]);
    }

    public function warehouseUtilization()
    {
        $utilization = Warehouse::with('itemUnits')->get()->map(function($w){
            $used = $w->itemUnits->sum('quantity');
            return [
                'warehouse' => $w->name,
                'capacity' => $w->capacity,
                'used' => $used,
                'percent' => $w->capacity ? round($used / $w->capacity * 100, 2) : 0
            ];
        });
        return Formatter::apiResponse(200, 'Warehouse utilization', $utilization);
    }

    public function categoryDistribution()
    {
        $itemsPerCategory = Category::withCount('items')->get(['name', 'items_count']);
        $itemUnitsPerCategory = Category::with(['items.itemUnits'])->get()->map(function($c){
            return [
                'category' => $c->name,
                'item_units' => $c->items->flatMap->itemUnits->sum('quantity')
            ];
        });
        return Formatter::apiResponse(200, 'Category distribution', [
            'items_per_category' => $itemsPerCategory,
            'item_units_per_category' => $itemUnitsPerCategory,
        ]);
    }

    public function timeTrends()
    {
        $monthlyUsers = User::select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('count(*) as total'))->groupBy('month')->get();
        $monthlyItems = Item::select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('count(*) as total'))->groupBy('month')->get();
        $monthlyBorrow = BorrowRequest::select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('count(*) as total'))->groupBy('month')->get();
        $monthlyReturn = ReturnRequest::select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('count(*) as total'))->groupBy('month')->get();
        return Formatter::apiResponse(200, 'Time-based trends', [
            'monthly_users' => $monthlyUsers,
            'monthly_items' => $monthlyItems,
            'monthly_borrow_requests' => $monthlyBorrow,
            'monthly_return_requests' => $monthlyReturn,
        ]);
    }

    public function alerts()
    {
        $pendingBorrow = BorrowRequest::query()->where('status', 'pending')->with(["user", "borrowDetails"])->simplePaginate(5);
        $pendingReturn = ReturnRequest::query()->where('status', 'pending')->with(["borrowRequest.user","returnDetails"])->simplePaginate(5);
        return Formatter::apiResponse(200, 'Pending Requests', [
            "pending_borrow_requests" => $pendingBorrow,
            "pending_return_requests" => $pendingReturn
        ]);
    }
}
