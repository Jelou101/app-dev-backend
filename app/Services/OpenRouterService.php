<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService
{
    private $apiKey;
    private $baseUrl;
    private $model;

    public function __construct()
    {
        $this->apiKey = env('OPENROUTER_API_KEY');
        $this->baseUrl = env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1');
        $this->model = env('OPENROUTER_MODEL', 'openai/gpt-oss-20b:free');
    }

    /**
     * Generate content using OpenRouter API
     */
    public function generateContent(string $prompt, float $temperature = 0.8, int $maxTokens = 200): ?string
    {
        if (!$this->apiKey) {
            Log::error('OpenRouter API key not configured');
            return null;
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'HTTP-Referer' => env('APP_URL', 'http://localhost'),
                    'X-Title' => env('APP_NAME', 'Laravel Puzzle'),
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl . '/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a helpful assistant that generates riddles and logic puzzles.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => $temperature,
                    'max_tokens' => $maxTokens,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? null;
            } else {
                Log::error('OpenRouter API failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('OpenRouter API Error: ' . $e->getMessage());
            return null;
        }
    }
}