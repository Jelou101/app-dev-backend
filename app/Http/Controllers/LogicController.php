<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Riddle;

class LogicController extends Controller
{
    /**
     * Generate an endurance question (logic only, multiple-choice) using AI
     */
    public function generate(Request $request)
    {
        $timeMode = (int) $request->query('time', 60);
        $questionNumber = (int) $request->query('q', 1);

        // Force type to logic
        $type = 'logic';

        // Generate AI logic question
        $question = $this->generateAILogicQuestion();

        // Fallback if AI fails
        if (!$question) {
            $question = $this->getFallbackQuestion($type);
        }

        // Add metadata
        $question['time_mode'] = $timeMode;
        $question['question_number'] = $questionNumber;
        $question['type'] = $type;

        return response()->json([
            'success' => true,
            'message' => 'Endurance question generated',
            'data' => $question
        ]);
    }

    /**
     * Generate AI riddle → now generates logic instead
     */
    private function generateAIRiddle()
    {
        return $this->generateAIQuestion('logic');
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
        $apiKey = env('GEMINI_API_KEY_5');
        if (!$apiKey) {
            Log::error('Gemini API key not configured');
            return null;
        }

        $themes = [
            'patterns' => 'Create a simple pattern-based logic puzzle',
            'math' => 'Create a simple math or numeric sequence puzzle'
        ];

        $selectedTheme = array_rand($themes);
        $themeInstruction = $themes[$selectedTheme];

        $prompt = "{$themeInstruction}. Generate a logic puzzle.\n\nFormat EXACTLY:\nQUESTION: [question]\nOPTIONS: [A-D]\nANSWER: [single letter]\nHINT: [short hint]\nEXPLANATION: [1-2 sentences]";

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

        if ($type === 'logic') {
            preg_match('/QUESTION:\s*(.+)/i', $text, $data['question']);
            preg_match('/OPTIONS:\s*(.+)/i', $text, $data['options']);
            preg_match('/ANSWER:\s*(.+)/i', $text, $data['answer']);
            preg_match('/HINT:\s*(.+)/i', $text, $data['hint']);
            preg_match('/EXPLANATION:\s*(.+)/i', $text, $data['explanation']);
        }

        if (empty($data['question']) || empty($data['answer'])) {
            return null;
        }

        // Convert options string to array
        $optionsArray = [];
        if (!empty($data['options'][1])) {
            preg_match_all('/([A-D])\)\s*([^A-D]+)/', $data['options'][1], $matches, PREG_SET_ORDER);
            foreach ($matches as $m) {
                $optionsArray[$m[1]] = trim($m[2]);
            }
        } else {
            // fallback empty options
            $optionsArray = ['A'=>'Option A','B'=>'Option B','C'=>'Option C','D'=>'Option D'];
        }

        $result = [
            'question' => trim($data['question'][1]),
            'answer' => strtolower(trim($data['answer'][1])),
            'hint' => $data['hint'][1] ?? 'Think carefully!',
            'explanation' => $data['explanation'][1] ?? 'No explanation available.',
            'type' => $type,
            'options' => $optionsArray
        ];

        return $result;
    }

    /**
     * Fallback questions if AI fails (logic only, multiple-choice)
     */
    private function getFallbackQuestion(string $type): array
    {
        $fallbacks = [
            [
                'question'=>'What comes next? △ □ ○ △ □ ___',
                'options'=>['A'=>'△','B'=>'□','C'=>'○','D'=>'☆'],
                'answer'=>'c',
                'hint'=>'Pattern repeats',
                'explanation'=>'△ □ ○ pattern repeats',
                'type'=>'logic'
            ],
            [
                'question'=>'1,1,2,3,5,8,13,?',
                'options'=>['A'=>'18','B'=>'20','C'=>'21','D'=>'19'],
                'answer'=>'c',
                'hint'=>'Fibonacci',
                'explanation'=>'8+13=21',
                'type'=>'logic'
            ],
            [
                'question'=>'If all Bloops are Razzies and all Razzies are Lazzies, are all Bloops Lazzies?',
                'options'=>['A'=>'Yes','B'=>'No','C'=>'Cannot say','D'=>'Maybe'],
                'answer'=>'a',
                'hint'=>'Use transitive relation',
                'explanation'=>'All Bloops are Lazzies through Razzies',
                'type'=>'logic'
            ],
            [
                'question'=>'Which number is the odd one out? 2, 3, 5, 7, 9',
                'options'=>['A'=>'2','B'=>'3','C'=>'7','D'=>'9'],
                'answer'=>'d',
                'hint'=>'Prime numbers',
                'explanation'=>'9 is not a prime number',
                'type'=>'logic'
            ]
        ];

        return $fallbacks[array_rand($fallbacks)];
    }
}
