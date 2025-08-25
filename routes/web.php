<?php

use App\Http\Controllers\AdminOpsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Admin endpoints (IP whitelist ve HMAC korumalı)
Route::prefix('admin')->group(function () {
    Route::post('/open-now', [AdminOpsController::class, 'openNow'])->name('admin.open-now');
    Route::get('/status', [AdminOpsController::class, 'status'])->name('admin.status');
});
