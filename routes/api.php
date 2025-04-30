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

Route::fallback(function () {
    return \App\Custom\Formatter::apiResponse(404, "Route not found");
});
