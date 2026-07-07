<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BonsaiController;
use App\Http\Controllers\Api\BonsaiEventController;
use App\Http\Controllers\Api\BonsaiImageController;
use App\Http\Controllers\Api\CalendarTaskController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TreatmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/register', [AuthController::class, 'register'])->middleware('guest');
    Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

    Route::middleware('auth')->group(function () {
        Route::get('/dashboard', DashboardController::class);
        Route::apiResource('/bonsais', BonsaiController::class);
        Route::post('/bonsais/{bonsai}/events', [BonsaiEventController::class, 'store']);
        Route::post('/bonsais/{bonsai}/images', [BonsaiImageController::class, 'store']);
        Route::post('/bonsais/{bonsai}/treatments', [TreatmentController::class, 'store']);
        Route::get('/calendar-tasks', [CalendarTaskController::class, 'index']);
        Route::post('/calendar-tasks', [CalendarTaskController::class, 'store']);
        Route::delete('/calendar-tasks/{calendarTask}', [CalendarTaskController::class, 'destroy']);
    });
});

Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
