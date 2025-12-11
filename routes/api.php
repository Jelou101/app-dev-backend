<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RiddleController;
use App\Http\Controllers\LogicController;
use App\Http\Controllers\EnduranceController;
use App\Http\Controllers\UserProgressController;
use App\Http\Controllers\UserStatusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

Route::post('/login', [UserController::class,'login']);
Route::post('/register', [UserController::class,'register']);

// User Progress API - Add these 3 lines
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/leaderboard', [UserController::class, 'getLeaderboard']);
    Route::get('/progress/{gameMode}', [UserProgressController::class, 'show']);
    Route::post('/progress/{gameMode}', [UserProgressController::class, 'update']);
    Route::post('/progress/{gameMode}/reset', [UserProgressController::class, 'reset']);
    


    Route::prefix('user-status')->group(function () {
        Route::get('/points', [UserStatusController::class, 'getPoints']);
        Route::post('/points/add', [UserStatusController::class, 'addPoints']);
        Route::post('/points/deduct', [UserStatusController::class, 'deductPoints']);
        Route::post('/points/game', [UserStatusController::class, 'addGamePoints']);
        Route::post('/points/reset', [UserStatusController::class, 'resetPoints']);
    });
});

// Riddles API
Route::get('/riddles', [RiddleController::class, 'index']);
Route::get('/riddles/{id}', [RiddleController::class, 'show']);
Route::post('/riddles', [RiddleController::class, 'store']);
Route::get('/riddles/generate/ai', [RiddleController::class, 'generate']);
Route::put('/riddles/{id}', [RiddleController::class, 'update']);
Route::delete('/riddles/{id}', [RiddleController::class, 'destroy']);
Route::get('/riddles/check-duplicates', [RiddleController::class, 'checkDuplicates']);
Route::get('/riddles/statistics', [RiddleController::class, 'statistics']);
Route::get('/test-openrouter', [RiddleController::class, 'testOpenRouter']);


// Logic Questions API
Route::get('/logic/generate', [LogicController::class, 'generate']);
// Logic question routes
Route::get('/logic', [LogicController::class, 'index']);
Route::get('/logic/{id}', [LogicController::class, 'show']);
Route::post('/logic', [LogicController::class, 'store']);
Route::put('/logic/{id}', [LogicController::class, 'update']);
Route::delete('/logic/{id}', [LogicController::class, 'destroy']);
Route::post('/logic/generate', [LogicController::class, 'generate']);
Route::get('/logic/statistics', [LogicController::class, 'statistics']);


// Endurance API (50 mixed riddle/logic questions)
Route::match(['get', 'post'], '/endurance/generate', [EnduranceController::class, 'generate']);

// Test route to check if Sanctum is working
// Test route to check if Sanctum is working
// Test route to check if Sanctum is working
// Test route to check if Sanctum is working
Route::middleware('auth:sanctum')->get('/test-auth', function (Request $request) {
    return response()->json([
        'message' => 'Authentication is working!',
        'user' => $request->user()  // Changed from auth()->user() to $request->user()
    ]);
});


Route::get('/list-gemini-models', function() {
    $apiKey = env('GEMINI_API_KEY');
    
    if (!$apiKey) {
        return response()->json(['error' => 'No Gemini API key found'], 500);
    }
    
    try {
        $response = Http::timeout(30)
            ->withoutVerifying()
            ->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
            
        if ($response->successful()) {
            $models = $response->json();
            $availableModels = [];
            
            foreach ($models['models'] as $model) {
                if (in_array('generateContent', $model['supportedGenerationMethods'] ?? [])) {
                    $availableModels[] = [
                        'name' => $model['name'],
                        'display_name' => $model['displayName'],
                        'description' => $model['description'] ?? '',
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'total_models' => count($models['models']),
                'generative_models' => $availableModels,
                'sample_models' => array_slice($availableModels, 0, 5) // First 5
            ]);
        } else {
            return response()->json([
                'success' => false,
                'status' => $response->status(),
                'body' => $response->body()
            ], 500);
        }
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
});

// Add this route in routes/api.php
Route::get('/test-gemini', function () {
    $apiKey = env('GEMINI_API_KEY');
    
    if (!$apiKey) {
        return response()->json(['error' => 'No API key'], 500);
    }
    
    try {
        $response = Http::withoutVerifying()
            ->timeout(30)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                'contents' => [[
                    'parts' => [['text' => 'Say hello in one word']]
                ]],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 10,
                ]
            ]);
        
        if ($response->successful()) {
            $data = $response->json();
            return response()->json([
                'success' => true,
                'response' => $data,
                'text' => $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No text'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'status' => $response->status(),
                'error' => $response->body()
            ]);
        }
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});

Route::get('/riddles/test', [RiddleController::class, 'testGenerate']);


// Add this to your routes/api.php
Route::get('/test-gemini-logic', function() {
    $apiKey = env('GEMINI_API_KEY_3');
    
    if (!$apiKey) {
        return response()->json(['error' => 'No API key'], 500);
    }
    
    try {
        $response = Http::timeout(30)
            ->withOptions(['verify' => false])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                'contents' => [[
                    'parts' => [['text' => 'Say "Gemini logic test working" in one word.']]
                ]],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 10,
                ]
            ]);
        
        if ($response->successful()) {
            $data = $response->json();
            return response()->json([
                'success' => true,
                'response' => $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No text',
                'status' => 'Connected'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'status' => $response->status(),
                'error' => $response->body()
            ]);
        }
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});