<?php

namespace App\Http\Controllers;

use App\Models\Riddle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RiddleController extends Controller
{
    /**
     * Return a list of riddles.
     */
    public function index()
    {
        $riddles = Riddle::orderBy('id', 'asc')->get(['id', 'question', 'hint', 'source']);
        return response()->json(['data' => $riddles], 200);
    }

    /**
     * Store a new riddle.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'question' => 'required|string',
            'answer' => 'nullable|string',
            'hint' => 'nullable|string',
            'source' => 'nullable|string',
            'explanation' => 'nullable|string',
        ]);

        $riddle = Riddle::create($data);

        return response()->json(['data' => $riddle], 201);
    }

    /**
     * Show a single riddle.
     */
    public function show($id)
    {
        $riddle = Riddle::find($id);
        if (!$riddle) {
            return response()->json(['message' => 'Riddle not found'], 404);
        }
        return response()->json(['data' => $riddle], 200);
    }

    /**
     * Update a riddle.
     */
    public function update(Request $request, $id)
    {
        $riddle = Riddle::find($id);
        if (!$riddle) {
            return response()->json(['message' => 'Riddle not found'], 404);
        }

        $data = $request->validate([
            'question' => 'sometimes|required|string',
            'answer' => 'nullable|string',
            'hint' => 'nullable|string',
            'source' => 'nullable|string',
            'explanation' => 'nullable|string',
        ]);

        $riddle->update($data);

        return response()->json(['data' => $riddle], 200);
    }

    /**
     * Delete a riddle.
     */
    public function destroy($id)
    {
        $riddle = Riddle::find($id);
        if (!$riddle) {
            return response()->json(['message' => 'Riddle not found'], 404);
        }

        $riddle->delete();
        return response()->json(['message' => 'Riddle deleted'], 200);
    }

    /**
     * Generate AI riddle with UNIQUE guarantee and API optimization
     */
    public function generate(Request $request)
    {
        Log::info("=== AI RIDDLE GENERATION STARTED (gemini-2.5-flash) ===");
        Log::info("API Key exists: " . (env('GEMINI_API_KEY') ? 'Yes' : 'No'));
        Log::info("Cache key: ai_riddle_today_" . now()->format('Y-m-d'));
        
        // Daily cache
        $cacheKey = null;
       
        
        $debugLog = ['timestamp' => now()->toDateTimeString()];
        
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            Log::error("No API key found");
            return response()->json([
                'success' => false,
                'message' => 'API key not configured',
                'error_code' => 'NO_API_KEY'
            ], 500);
        }
        
        // Get existing answers for uniqueness
        $existingAnswers = Riddle::pluck('answer')->map(function($ans) {
            return strtolower(trim($ans));
        })->toArray();
        
        $debugLog['existing_unique_answers'] = count(array_unique($existingAnswers));
        
        // ========== VARIED THEMES (not just animals) ==========
        $themes = [
            'animals' => 'Create a riddle about an animal',
            'objects' => 'Create a riddle about a common household object',
            'nature' => 'Create a riddle about something in nature (not animals)',
            'food' => 'Create a riddle about food or drink',
            'technology' => 'Create a riddle about technology or electronics',
            'body' => 'Create a riddle about a body part or human feature',
            'time' => 'Create a riddle about time or something related to time',
            'transportation' => 'Create a riddle about transportation or vehicles'
        ];
        
        // Select random theme (not just animals)
        $themeKeys = array_keys($themes);
        $selectedTheme = $themeKeys[array_rand($themeKeys)];
        $themeInstruction = $themes[$selectedTheme];
        
        $debugLog['selected_theme'] = $selectedTheme;
        
        // ========== SIMPLIFIED PROMPT - FIXED FORMAT ISSUE ==========
        $prompt = "{$themeInstruction}. Answer must be one word.

Format your response EXACTLY like this (4 lines):

RIDDLE: [Your riddle question ending with ?]
HINT: [2-4 word hint]
ANSWER: [one word lowercase]
EXPLANATION: [1 sentence]

Example:
RIDDLE: What has keys but can't open locks?
HINT: Musical instrument
ANSWER: piano
EXPLANATION: A piano has keys for playing music, not for opening doors.

Create a NEW riddle now:";
        
        $debugLog['prompt_length'] = strlen($prompt);
        $debugLog['prompt_tokens_approx'] = ceil(strlen($prompt) / 4);
        
        // ========== MAKE API CALL WITH OPTIMIZED CONFIG ==========
        $maxRetries = 3;
        $attempt = 0;
        $aiText = '';
        
        while ($attempt < $maxRetries) {
            $attempt++;
            Log::info("API attempt {$attempt}/{$maxRetries} with gemini-2.5-flash");
            
            try {
                // CRITICAL CHANGE: Using gemini-2.5-flash with optimized settings
                $response = Http::withoutVerifying()
                    ->timeout(45)
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                        'contents' => [[
                            'parts' => [['text' => $prompt]]
                        ]],
                        'generationConfig' => [
                            'temperature' => 0.8, // Slightly higher for creativity
                            'maxOutputTokens' => 1500, // Increased for better responses
                            'topP' => 0.9,
                            'topK' => 40,
                        ],
                        'safetySettings' => [
                            [
                                'category' => 'HARM_CATEGORY_HARASSMENT',
                                'threshold' => 'BLOCK_NONE'
                            ],
                            [
                                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                                'threshold' => 'BLOCK_NONE'
                            ],
                            [
                                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                                'threshold' => 'BLOCK_NONE'
                            ],
                            [
                                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                                'threshold' => 'BLOCK_NONE'
                            ]
                        ]
                    ]);

                $debugLog['response_status'] = $response->status();
                
                if ($response->successful()) {
                    $data = $response->json();
                    $debugLog['model_version'] = $data['modelVersion'] ?? 'unknown';
                    $finishReason = $data['candidates'][0]['finishReason'] ?? 'unknown';
                    $debugLog['finish_reason'] = $finishReason;
                    $debugLog['total_tokens'] = $data['usageMetadata']['totalTokenCount'] ?? 0;
                    $debugLog['prompt_tokens'] = $data['usageMetadata']['promptTokenCount'] ?? 0;
                    
                    $aiText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    
                    Log::info("Attempt {$attempt}: Finish Reason = {$finishReason}, Text Length = " . strlen($aiText));
                    
                    // Check if we got a valid response
                    if ($finishReason === 'STOP' && !empty($aiText)) {
                        // Success! Break the retry loop
                        $debugLog['ai_text_raw'] = substr($aiText, 0, 200);
                        $debugLog['ai_text_length'] = strlen($aiText);
                        Log::info("✅ Valid AI response received on attempt {$attempt}");
                        break;
                    } elseif ($finishReason === 'MAX_TOKENS') {
                        Log::warning("MAX_TOKENS on attempt {$attempt} - tokens used: " . ($data['usageMetadata']['totalTokenCount'] ?? 0));
                        if ($attempt < $maxRetries) {
                            sleep(1);
                            continue;
                        }
                    } elseif ($finishReason === 'SAFETY' || $finishReason === 'RECITATION') {
                        Log::warning("Safety/recitation block on attempt {$attempt}");
                        if ($attempt < $maxRetries) {
                            // Try with different theme
                            $selectedTheme = $themeKeys[array_rand($themeKeys)];
                            $themeInstruction = $themes[$selectedTheme];
                            $prompt = str_replace($themes[$selectedTheme], $themeInstruction, $prompt);
                            Log::info("Switched theme to: {$selectedTheme}");
                            sleep(1);
                            continue;
                        }
                    } elseif (empty($aiText)) {
                        Log::warning("Empty response on attempt {$attempt}");
                        if ($attempt < $maxRetries) {
                            sleep(2);
                            continue;
                        }
                    }
                } else {
                    $error = $response->json();
                    $debugLog['api_error'] = $error['error']['message'] ?? 'Unknown error';
                    $debugLog['status_code'] = $response->status();
                    
                    Log::error("API error on attempt {$attempt}", ['error' => $debugLog['api_error']]);
                    
                    // Check for specific errors
                    if (str_contains($debugLog['api_error'] ?? '', 'quota') || $response->status() === 429) {
                        Log::error("API quota exceeded - stopping retries");
                        break;
                    }
                    
                    if ($attempt < $maxRetries) {
                        sleep(3);
                        continue;
                    }
                }
            } catch (\Exception $e) {
                $debugLog['exception'] = $e->getMessage();
                Log::error("Exception on attempt {$attempt}", ['error' => $e->getMessage()]);
                
                if ($attempt < $maxRetries) {
                    sleep(2);
                    continue;
                }
            }
        }
        
        // ========== PROCESS AI RESPONSE ==========
        if (!empty($aiText)) {
            // Parse the AI response
            $parsed = $this->parseRiddleResponseRobust($aiText);
            
            if ($parsed['success']) {
                // Check for duplicate answer
                $answer = strtolower(trim($parsed['answer']));
                
                // Check if answer is valid
                if (strlen($answer) < 2) {
                    Log::warning("AI generated invalid answer: {$answer}");
                    return response()->json([
                        'success' => false,
                        'message' => 'AI generated invalid answer',
                        'error_code' => 'INVALID_ANSWER',
                        'debug' => $debugLog
                    ], 422);
                }
                
                if (!in_array($answer, $existingAnswers)) {
                    // Save AI riddle
                    $riddle = Riddle::create([
                        'question' => $parsed['question'],
                        'hint' => $parsed['hint'],
                        'answer' => $answer,
                        'explanation' => $parsed['explanation'],
                        'source' => 'gemini_ai'
                    ]);
                    
                    // Cache the AI result
                    $cacheData = [
                        'ai_generated' => true,
                        'data' => [
                            'id' => $riddle->id,
                            'question' => $riddle->question,
                            'hint' => $riddle->hint,
                            'answer' => $riddle->answer,
                            'explanation' => $riddle->explanation,
                            'source' => $riddle->source,
                            'theme' => $selectedTheme
                        ]
                    ];
                    
                    Cache::put($cacheKey, $cacheData, now()->addDay());
                    
                    Log::info("✅ AI riddle success - gemini-2.5-flash", [
                        'id' => $riddle->id,
                        'answer' => $answer,
                        'theme' => $selectedTheme,
                        'attempts' => $attempt,
                        'tokens_used' => $debugLog['total_tokens'] ?? 0
                    ]);

                    return response()->json([
                        'success' => true,
                        'ai_generated' => true,
                        'unique' => true,
                        'attempts' => $attempt,
                        'model' => 'gemini-2.5-flash',
                        'message' => 'AI riddle generated successfully!',
                        'data' => $cacheData['data'],
                        'theme' => $selectedTheme
                    ]);
                    
                } else {
                    Log::warning("AI generated duplicate answer: {$answer}");
                    return response()->json([
                        'success' => false,
                        'message' => 'AI generated duplicate answer',
                        'error_code' => 'DUPLICATE_ANSWER',
                        'answer' => $answer,
                        'debug' => $debugLog
                    ], 422);
                }
                
            } else {
                Log::warning("Parse error: " . $parsed['error']);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to parse AI response',
                    'error_code' => 'PARSE_ERROR',
                    'error' => $parsed['error'],
                    'debug' => $debugLog,
                    'ai_text_preview' => substr($aiText, 0, 200)
                ], 422);
            }
            
        } else {
            // All retries failed
            Log::error("All {$maxRetries} attempts failed to get valid AI response", $debugLog);
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate AI riddle after ' . $maxRetries . ' attempts',
                'error_code' => 'AI_GENERATION_FAILED',
                'debug' => $debugLog,
                'model' => 'gemini-2.5-flash'
            ], 503);
        }
    }
    
    /**
     * ROBUST PARSER - Ultra reliable
     */
    private function parseRiddleResponseRobust(string $text): array
    {
        $text = trim($text);
        
        // Remove any markdown formatting
        $text = str_replace(['```', '**', '*', '`'], '', $text);
        
        // Normalize line endings
        $text = preg_replace('/\r\n/', "\n", $text);
        
        $result = [
            'question' => '',
            'hint' => '',
            'answer' => '',
            'explanation' => ''
        ];
        
        // METHOD 1: Strict regex parsing with line-by-line
        $lines = array_map('trim', explode("\n", $text));
        $lines = array_values(array_filter($lines, function($line) {
            return !empty($line) && strlen($line) > 2;
        }));
        
        // Look for exact patterns
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Check for RIDDLE: prefix
            if (preg_match('/^RIDDLE:\s*(.+)$/i', $line, $matches)) {
                $result['question'] = trim($matches[1]);
            }
            // Check for HINT: prefix
            elseif (preg_match('/^HINT:\s*(.+)$/i', $line, $matches)) {
                $result['hint'] = trim($matches[1]);
            }
            // Check for ANSWER: prefix
            elseif (preg_match('/^ANSWER:\s*(.+)$/i', $line, $matches)) {
                $result['answer'] = trim($matches[1]);
            }
            // Check for EXPLANATION: prefix
            elseif (preg_match('/^EXPLANATION:\s*(.+)$/i', $line, $matches)) {
                $result['explanation'] = trim($matches[1]);
            }
        }
        
        // METHOD 2: If missing parts, infer from structure
        if (empty($result['question']) && count($lines) > 0) {
            // First line with ? is probably the riddle
            foreach ($lines as $line) {
                if (str_contains($line, '?')) {
                    $result['question'] = trim(str_ireplace('RIDDLE:', '', $line));
                    break;
                }
            }
            // If still empty, use first line
            if (empty($result['question'])) {
                $result['question'] = $lines[0];
            }
        }
        
        if (empty($result['answer'])) {
            // Look for a single word line (likely answer)
            foreach ($lines as $line) {
                $cleanLine = trim(str_ireplace(['ANSWER:', 'answer:'], '', $line));
                if (preg_match('/^[a-zA-Z]{2,15}$/', $cleanLine)) {
                    $result['answer'] = $cleanLine;
                    break;
                }
            }
        }
        
        if (empty($result['hint']) && count($lines) > 1) {
            // Second line might be hint if it's short
            if (isset($lines[1]) && strlen($lines[1]) < 50) {
                $result['hint'] = trim(str_ireplace('HINT:', '', $lines[1]));
            }
        }
        
        if (empty($result['explanation']) && count($lines) > 2) {
            // Last line or longer line might be explanation
            $lastLine = end($lines);
            if (strlen($lastLine) > 20) {
                $result['explanation'] = trim(str_ireplace('EXPLANATION:', '', $lastLine));
            }
        }
        
        // Clean and validate answer
        if (!empty($result['answer'])) {
            $original = $result['answer'];
            $result['answer'] = strtolower(trim($result['answer']));
            // Remove any non-letter characters from start/end only
            $result['answer'] = preg_replace('/^[^a-z]+|[^a-z]+$/i', '', $result['answer']);
            
            // If answer became too short, use original
            if (strlen($result['answer']) < 2) {
                $result['answer'] = strtolower(preg_replace('/[^a-z]/i', '', $original));
            }
        }
        
        // Ensure explanation exists
        if (empty($result['explanation']) && !empty($result['answer'])) {
            $result['explanation'] = "The answer '{$result['answer']}' fits the riddle's description.";
        } elseif (empty($result['explanation'])) {
            $result['explanation'] = "This answer correctly matches all the clues in the riddle.";
        }
        
        // Ensure hint exists
        if (empty($result['hint'])) {
            $result['hint'] = "Think carefully about the clues!";
        }
        
        // Ensure question ends with ?
        if (!empty($result['question']) && !str_ends_with(trim($result['question']), '?')) {
            $result['question'] = rtrim($result['question'], '.!') . '?';
        }
        
        // Final validation
        if (empty($result['question']) || empty($result['answer'])) {
            return [
                'success' => false, 
                'error' => 'Missing question or answer',
                'data' => $result,
                'raw_lines' => $lines
            ];
        }
        
        return ['success' => true] + $result;
    }
    
    /**
     * Get statistics about riddles
     */
    public function statistics()
    {
        $total = Riddle::count();
        $bySource = Riddle::select('source', DB::raw('count(*) as count'))
            ->groupBy('source')
            ->get()
            ->pluck('count', 'source');
        
        $uniqueAnswers = Riddle::distinct('answer')->count('answer');
        
        // Check for potential duplicates
        $potentialDuplicates = [];
        $riddles = Riddle::all();
        
        foreach ($riddles as $i => $r1) {
            foreach ($riddles as $j => $r2) {
                if ($i < $j && $r1->answer === $r2->answer) {
                    $potentialDuplicates[] = [
                        'answer' => $r1->answer,
                        'ids' => [$r1->id, $r2->id],
                        'questions' => [substr($r1->question, 0, 50), substr($r2->question, 0, 50)]
                    ];
                }
            }
        }
        
        return response()->json([
            'total_riddles' => $total,
            'unique_answers' => $uniqueAnswers,
            'by_source' => $bySource,
            'potential_duplicates' => [
                'count' => count($potentialDuplicates),
                'examples' => array_slice($potentialDuplicates, 0, 5)
            ],
            'cache_status' => [
                'today_ai_cached' => Cache::has('ai_riddle_today_' . now()->format('Y-m-d')),
                'next_ai_allowed' => 'tomorrow'
            ]
        ]);
    }
    
    /**
     * Manually clear cache for testing
     */
    public function clearCache()
    {
        $key = 'ai_riddle_today_' . now()->format('Y-m-d');
        $wasCached = Cache::has($key);
        Cache::forget($key);
        
        return response()->json([
            'success' => true,
            'message' => $wasCached ? 'Cache cleared. Next AI call allowed.' : 'No cache to clear.',
            'next_ai_call' => 'allowed now'
        ]);
    }
    
    /**
     * TEST ENDPOINT - for debugging AI API
     */
    public function testAIApi(Request $request)
    {
        $apiKey = env('GEMINI_API_KEY');
        
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'No API key configured'
            ], 500);
        }
        
        // Use a simpler prompt for testing
        $testPrompt = "Create a short riddle about an animal. Answer must be one word. Format: RIDDLE: [question] HINT: [hint] ANSWER: [answer] EXPLANATION: [explanation]";
        
        try {
            $response = Http::withoutVerifying()
                ->timeout(30)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                    'contents' => [[
                        'parts' => [['text' => $testPrompt]]
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 500,
                    ]
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'model' => 'gemini-2.5-flash',
                    'finish_reason' => $data['candidates'][0]['finishReason'] ?? 'unknown',
                    'has_text' => isset($data['candidates'][0]['content']['parts'][0]['text']),
                    'text' => $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No text',
                    'usage' => $data['usageMetadata'] ?? [],
                    'model_version' => $data['modelVersion'] ?? 'unknown'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'model' => 'gemini-2.5-flash',
                    'status' => $response->status(),
                    'error' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'model' => 'gemini-2.5-flash',
                'error' => $e->getMessage()
            ]);
        }
    }
}