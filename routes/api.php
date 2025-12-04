<?php

use App\Http\Controllers\MqttController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/mqtt-connections', [MqttController::class, 'index']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
