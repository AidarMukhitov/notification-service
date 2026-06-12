<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::post('/broadcast', [NotificationController::class, 'broadcast']);
Route::get('/subscribers/{subscriberId}/notifications', [NotificationController::class, 'history']);
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'service' => 'Notification Service']);
});