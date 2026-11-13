<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProctorController;
use App\Http\Controllers\Api\StudentController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('api.token')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware(['api.token', 'role:student'])->prefix('student')->group(function () {
    Route::get('dashboard', [StudentController::class, 'dashboard']);
    Route::get('examinations', [StudentController::class, 'history']);
    Route::post('examinations/join', [StudentController::class, 'join']);
    Route::get('examinations/{examination}', [StudentController::class, 'show']);
    Route::post('examinations/{examination}/start', [StudentController::class, 'start']);
    Route::post('examinations/{examination}/finish', [StudentController::class, 'finish']);
    Route::post('examinations/{examination}/activity', [StudentController::class, 'activity']);
});

Route::middleware(['api.token', 'role:proctor'])->prefix('proctor')->group(function () {
    Route::get('dashboard', [ProctorController::class, 'dashboard']);
    Route::get('examinations', [ProctorController::class, 'examinations']);
    Route::post('examinations', [ProctorController::class, 'storeExamination']);
    Route::get('examinations/{examination}', [ProctorController::class, 'showExamination']);
    Route::patch('examinations/{examination}/start', [ProctorController::class, 'startExamination']);
    Route::patch('examinations/{examination}/end', [ProctorController::class, 'endExamination']);

    Route::get('alerts', [ProctorController::class, 'alerts']);
    Route::post('incidents', [ProctorController::class, 'storeIncident']);
    Route::patch('incidents/{incident}/status', [ProctorController::class, 'updateIncidentStatus']);
});
