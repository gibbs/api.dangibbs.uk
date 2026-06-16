<?php

use App\Http\Controllers\API\V1 as Controllers;
use Illuminate\Support\Facades\Route;

Route::get('/feeds/activity', Controllers\Feeds\ActivityController::class);

Route::prefix('tools')->middleware(['throttle:60,1', 'auth:sanctum'])->group(function () {
    Route::post('/dig', Controllers\Tools\DigController::class);
    Route::post('/pwgen', Controllers\Tools\PwgenController::class);
    Route::post('/mkpasswd', Controllers\Tools\MkpasswdController::class);
    Route::post('/uuid', Controllers\Tools\UuidController::class);
});
