<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix("auth")->group(function () {
   Route::post("login", [\App\Http\Controllers\AuthController::class, "login"]);
   Route::middleware("need-token")->group(function () {
      Route::get("logout", [\App\Http\Controllers\AuthController::class, "logout"]);
      Route::get("self", [\App\Http\Controllers\AuthController::class, "self"]);
   });
});

Route::apiResource("categories", \App\Http\Controllers\CategoryController::class)->only(["index","show"]);
Route::apiResource("warehouses", \App\Http\Controllers\WarehouseController::class)->only(["index","show"]);
Route::apiResource("items", \App\Http\Controllers\ItemController::class)->only(["index","show"]);
Route::apiResource("itemUnits", \App\Http\Controllers\ItemUnitController::class)->only(["index","show"]);

Route::middleware("need-token")->group(function () {
   Route::prefix("admin")->middleware("role:admin")->group(function () {
      Route::apiResource("users", \App\Http\Controllers\UserController::class);
      Route::post('users/import', [\App\Http\Controllers\UserController::class, 'importUsers']);
      
      Route::apiResource("warehouses", \App\Http\Controllers\WarehouseController::class);
      Route::post('warehouses/import', [\App\Http\Controllers\WarehouseController::class, 'importWarehouses']);
      
      Route::apiResource("categories", \App\Http\Controllers\CategoryController::class);
      Route::post('categories/import', [\App\Http\Controllers\CategoryController::class, 'importCategories']);

      Route::apiResource("items", \App\Http\Controllers\ItemController::class)->except(["update"]);
      Route::post("items/{id}", [\App\Http\Controllers\ItemController::class, "update"]);
      Route::post('items/import', [\App\Http\Controllers\ItemController::class, 'importItems']);

      Route::apiResource("itemUnits", \App\Http\Controllers\ItemUnitController::class);
      Route::post('itemUnits/import', [\App\Http\Controllers\ItemUnitController::class, 'importItemUnits']);

      Route::apiResource("borrow-requests", \App\Http\Controllers\BorrowRequestController::class)->only(["index","show"]);
      Route::patch("borrow-requests/{id}/approve", [\App\Http\Controllers\BorrowRequestController::class, "approve"]);
      Route::patch("borrow-requests/{id}/reject", [\App\Http\Controllers\BorrowRequestController::class, "reject"]);

       Route::apiResource("return-requests", \App\Http\Controllers\ReturnRequestController::class)->only(["index","show"]);
       Route::patch("return-requests/{id}/approve", [\App\Http\Controllers\ReturnRequestController::class, "approve"]);
       Route::patch("return-requests/{id}/reject", [\App\Http\Controllers\ReturnRequestController::class, "reject"]);

      Route::apiResource("logs", \App\Http\Controllers\LogActivityController::class)->only(["index","show"]);

      Route::apiResource("borrowRequests", \App\Http\Controllers\BorrowRequestController::class);
      Route::post('borrowRequests/import', [\App\Http\Controllers\BorrowRequestController::class, 'importBorrowRequests']);
      Route::get('borrowRequests/export/pdf', [\App\Http\Controllers\BorrowRequestController::class, 'exportPdf']);
      
      Route::apiResource("returnRequests", \App\Http\Controllers\ReturnRequestController::class);
      Route::post('returnRequests/import', [\App\Http\Controllers\ReturnRequestController::class, 'importReturnRequests']);
      Route::get('returnRequests/export/pdf', [\App\Http\Controllers\ReturnRequestController::class, 'exportPdf']);

      Route::prefix('dashboard')->group(function () {
        Route::get('entity-counts', [\App\Http\Controllers\DashboardController::class, 'entityCounts']);
        Route::get('stock-stats', [\App\Http\Controllers\DashboardController::class, 'stockStats']);
        Route::get('borrow-return-stats', [\App\Http\Controllers\DashboardController::class, 'borrowReturnStats']);
        Route::get('user-activity', [\App\Http\Controllers\DashboardController::class, 'userActivity']);
        Route::get('warehouse-utilization', [\App\Http\Controllers\DashboardController::class, 'warehouseUtilization']);
        Route::get('category-distribution', [\App\Http\Controllers\DashboardController::class, 'categoryDistribution']);
        Route::get('time-trends', [\App\Http\Controllers\DashboardController::class, 'timeTrends']);
        Route::get('alerts', [\App\Http\Controllers\DashboardController::class, 'alerts']);
      });
   });

   Route::middleware("role:user")->group(function () {
       Route::apiResource("borrow-requests", \App\Http\Controllers\BorrowRequestController::class)->except(["update","destroy"]);
       Route::apiResource("return-requests", \App\Http\Controllers\ReturnRequestController::class)->except(["update","destroy"]);
   });
});

Route::fallback(function () {
    return \App\Custom\Formatter::apiResponse(404, "Route not found");
});
