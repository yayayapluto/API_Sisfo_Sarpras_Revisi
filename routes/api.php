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

Route::middleware("need-token")->group(function () {
   Route::prefix("admin")->middleware("role:admin")->group(function () {
      Route::apiResource("categories", \App\Http\Controllers\CategoryController::class);
      Route::apiResource("warehouses", \App\Http\Controllers\WarehouseController::class);
      Route::apiResource("items", \App\Http\Controllers\ItemController::class);
      Route::apiResource("itemUnits", \App\Http\Controllers\ItemUnitController::class);
      Route::apiResource("logs", \App\Http\Controllers\LogActivityController::class)->only(["index","show"]);
   });
});

Route::fallback(function () {
    return \App\Custom\Formatter::apiResponse(404, "Route not found");
});
