<?php

namespace App\Http\Controllers;

use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserProgressController extends Controller
{
    /**
     * Get user progress for a game mode
     */
    public function show($gameMode)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $progress = UserProgress::firstOrCreate(
            ['user_id' => $user->user_id, 'game_mode' => $gameMode],
            [
                'current_score' => 0,
                'hint_count' => 0,
                'attempted_riddles' => []
            ]
        );

        return response()->json(['data' => $progress], 200);
    }

    /**
     * Save progress
     */
    public function update(Request $request, $gameMode)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'current_score' => 'sometimes|integer',
            'hint_count' => 'sometimes|integer',
            'current_riddle' => 'sometimes|array',
            'attempted_riddles' => 'sometimes|array',
        ]);

        $progress = UserProgress::updateOrCreate(
            ['user_id' => $user->user_id, 'game_mode' => $gameMode],
            array_merge($data, ['last_played_at' => now()])
        );

        return response()->json(['data' => $progress], 200);
    }

    /**
     * Reset progress
     */
    public function reset($gameMode)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        UserProgress::where('user_id', $user->user_id)
            ->where('game_mode', $gameMode)
            ->update([
                'current_score' => 0,
                'hint_count' => 0,
                'current_riddle' => null,
                'attempted_riddles' => [],
                'last_played_at' => now()
            ]);

        return response()->json(['message' => 'Progress reset'], 200);
    }
}