<?php

use Illuminate\Support\Facades\Route;

Route::fallback(function () {
    return \App\Custom\Formatter::apiResponse(404, "Route not found");
});
