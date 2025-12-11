<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
Route::get('/', function () {
    return view('welcome');
});


// Test routes
Route::get('/test-api', [App\Http\Controllers\TestController::class, 'testAPI']);

Route::get('/test-ai-call', [App\Http\Controllers\TestController::class, 'testAICall']);
Route::get('/list-models', [App\Http\Controllers\TestController::class, 'listModels']);