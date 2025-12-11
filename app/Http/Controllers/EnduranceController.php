<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Riddle;

class EnduranceController extends Controller
{
    /**
     * Generate an endurance question (mix of riddles and logic) using AI
     */
    public function generate(Request $request)
    {
        $timeMode = (int) $request->query('time', 60);
        $questionNumber = (int) $request->query('q', 1);

        // Randomly select type
        $type = rand(1, 2) === 1 ? 'riddle' : 'logic';

        // Generate AI question
        $question = $type === 'riddle' ? $this->generateAIRiddle() : $this->generateAILogicQuestion();

        // Fallback if AI fails
        if (!$question) {
            $question = $this->getFallbackQuestion($type);
        }

        // Add metadata
        $question['time_mode'] = $timeMode;
        $question['question_number'] = $questionNumber;
        $question['type'] = $type;
        
        // Ensure 'options' is unset or null for all question types
        unset($question['options']); 

        return response()->json([
            'success' => true,
            'message' => 'Endurance question generated',
            'data' => $question
        ]);
    }

    /**
     * Generate AI riddle
     */
    private function generateAIRiddle()
    {
        return $this->generateAIQuestion('riddle');
    }

    /**
     * Generate AI logic question
     */
    private function generateAILogicQuestion()
    {
        return $this->generateAIQuestion('logic');
    }

    /**
     * Unified AI generation function
     */
    private function generateAIQuestion(string $type)
    {
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            Log::error('Gemini API key not configured');
            return null;
        }

        $existingAnswers = Riddle::pluck('answer')->map(fn($a) => strtolower(trim($a)))->toArray();
        $themes = $type === 'riddle'
            ? [
                'animals' => 'Create a riddle about an animal',
                'objects' => 'Create a riddle about a common object',
                'nature' => 'Create a riddle about nature',
                'food' => 'Create a riddle about food or drink'
            ]
            : [
                // Logic puzzles must now have a clear, non-multiple-choice answer (e.g., a number, a word, or a simple sequence item).
                'patterns' => 'Create a simple pattern-based logic puzzle that requires a single numeric or word answer',
                'math' => 'Create a simple math or numeric sequence puzzle where the user must provide the missing number'
            ];

        $selectedTheme = array_rand($themes);
        $themeInstruction = $themes[$selectedTheme];

        // MODIFICATION 1: Update Prompts to remove OPTIONS instructions.
        $prompt = $type === 'riddle'
            ? "{$themeInstruction}. Answer must be one word.\n\nFormat EXACTLY:\nRIDDLE: [question]\nHINT: [hint]\nANSWER: [one word lowercase]\nEXPLANATION: [1 sentence]"
            // Logic prompt changed to request a single-word or numeric answer.
            : "{$themeInstruction}. Generate a logic puzzle that has a clear, single-word or numeric answer.\n\nFormat EXACTLY:\nQUESTION: [question]\nANSWER: [the correct single word or number]\nHINT: [short hint]\nEXPLANATION: [1-2 sentences]";

        try {
            $response = Http::withoutVerifying()
                ->timeout(45)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'temperature' => 0.8,
                        'maxOutputTokens' => 1500,
                        'topP' => 0.9,
                        'topK' => 40
                    ]
                ]);

            if ($response->successful()) {
                $aiText = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
                return $this->parseAIResponse($aiText, $type);
            }

            Log::error("AI API failed: " . $response->body());
        } catch (\Exception $e) {
            Log::error("AI Generation Error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Parse AI response
     */
    private function parseAIResponse(string $text, string $type): ?array
    {
        $text = trim(str_replace(['```', '**', '*'], '', $text));

        $data = [];
        if ($type === 'riddle') {
            preg_match('/RIDDLE:\s*(.+)/i', $text, $data['question']);
            preg_match('/HINT:\s*(.+)/i', $text, $data['hint']);
            preg_match('/ANSWER:\s*(.+)/i', $text, $data['answer']);
            preg_match('/EXPLANATION:\s*(.+)/i', $text, $data['explanation']);
        } else {
            // MODIFICATION 2: Removed OPTIONS parsing for logic questions
            preg_match('/QUESTION:\s*(.+)/i', $text, $data['question']);
            preg_match('/ANSWER:\s*(.+)/i', $text, $data['answer']);
            preg_match('/HINT:\s*(.+)/i', $text, $data['hint']);
            preg_match('/EXPLANATION:\s*(.+)/i', $text, $data['explanation']);
        }

        if (empty($data['question']) || empty($data['answer'])) {
            return null;
        }

        $result = [
            'question' => trim($data['question'][1]),
            'answer' => strtolower(trim($data['answer'][1])),
            'hint' => $data['hint'][1] ?? 'Think carefully!',
            'explanation' => $data['explanation'][1] ?? 'No explanation available.',
            'type' => $type
        ];

        // MODIFICATION 2 (continued): Ensure 'options' is never added to the result array
        // The check 'if ($type === 'logic') { ... }' is now removed.

        return $result;
    }

    /**
     * Fallback questions if AI fails
     */
    private function getFallbackQuestion(string $type): array
    {
        if ($type === 'riddle') {
            $fallbacks = [
                ['question' => "What has keys but can't open locks?", 'hint' => "Think musical", 'answer' => 'piano', 'explanation' => 'A piano has keys but they are musical keys.','type'=>'riddle'],
                ['question' => "What has a head and tail but no body?", 'hint' => "Think coins", 'answer' => 'coin', 'explanation' => 'A coin has a head and tail but no body.','type'=>'riddle'],
            ];
        } else {
            // MODIFICATION 3: Removed OPTIONS from fallback logic questions
            $fallbacks = [
                ['question'=>'What comes next in the sequence: 2, 4, 8, 16, ?','answer'=>'32','hint'=>'The numbers are doubling.','explanation'=>'The sequence is a power of 2: $2^1, 2^2, 2^3, 2^4, 2^5$. The answer is 32.','type'=>'logic'],
                ['question'=>'What is the next number in the Fibonacci sequence: 1, 1, 2, 3, 5, 8, 13, ?','answer'=>'21','hint'=>'Add the previous two numbers together.','explanation'=>'The sum of the last two numbers ($8 + 13$) equals the next number, 21.','type'=>'logic']
            ];
        }
        return $fallbacks[array_rand($fallbacks)];
    }
}