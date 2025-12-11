<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    public function testAPI()
    {
        // Get API key from .env
        $apiKey = env('GEMINI_API_KEY');
        
        // Show first 10 characters of API key
        echo "API Key (first 10 chars): " . substr($apiKey, 0, 10) . "...<br>";
        
        // Show full key (masked)
        $maskedKey = substr($apiKey, 0, 4) . str_repeat('*', strlen($apiKey) - 8) . substr($apiKey, -4);
        echo "API Key (masked): $maskedKey<br><br>";
        
        // Check if key exists
        if ($apiKey) {
            echo "✅ API key found in .env<br>";
        } else {
            echo "❌ API key NOT found<br>";
            return;
        }
        
        echo "<br>Click this link to test if API works: ";
        echo "<a href='/test-ai-call'>Test AI Call</a>";
    }

    public function testAICall()
    {
        $apiKey = env('GEMINI_API_KEY');
        
        echo "<h1>Testing Gemini AI Connection</h1>";
        echo "<p>API Key loaded: " . substr($apiKey, 0, 10) . "...</p>";
        
        try {
            // Simple API call
            $response = \Illuminate\Support\Facades\Http::timeout(10)->withoutVerifying()
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => 'Say "Hello from PHP"']
                            ]
                        ]
                    ]
                ]);
            
            if ($response->successful()) {
                echo "<p style='color: green; font-weight: bold;'>✅ SUCCESS! AI is responding!</p>";
                
                $data = $response->json();
                $aiText = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No text in response';
                
                echo "<p><strong>AI Response:</strong></p>";
                echo "<pre>" . htmlspecialchars($aiText) . "</pre>";
                
                echo "<p style='color: green;'>🎉 Your Gemini API is WORKING! You can now generate AI riddles!</p>";
                
            } else {
                echo "<p style='color: red; font-weight: bold;'>❌ ERROR: API returned status " . $response->status() . "</p>";
                echo "<pre>" . htmlspecialchars($response->body()) . "</pre>";
            }
            
        } catch (\Exception $e) {
            echo "<p style='color: red; font-weight: bold;'>❌ EXCEPTION: " . $e->getMessage() . "</p>";
        }
        
        echo "<hr>";
        echo "<p><a href='/test-api'>Go back</a> | ";
        echo "<a href='/'>Home</a></p>";
    }

    public function listModels()
    {
        $apiKey = env('GEMINI_API_KEY');
        
        echo "<h1>Listing Available Gemini Models</h1>";
        
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)->withoutVerifying()
                ->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
            
            if ($response->successful()) {
                $data = $response->json();
                
                echo "<p style='color: green;'>✅ Success! Found models:</p>";
                echo "<ul>";
                foreach ($data['models'] as $model) {
                    echo "<li><strong>{$model['name']}</strong> - {$model['description']}</li>";
                }
                echo "</ul>";
                
                // Try the first model
                $firstModel = explode('/', $data['models'][0]['name']);
                $modelName = end($firstModel);
                
                echo "<p>Try this model: <strong>{$modelName}</strong></p>";
                echo "<a href='/test-ai-call?model={$modelName}'>Test with {$modelName}</a>";
                
            } else {
                echo "<p style='color: red;'>Error: " . $response->status() . "</p>";
                echo "<pre>" . htmlspecialchars($response->body()) . "</pre>";
            }
            
        } catch (\Exception $e) {
            echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
        }
    }
}