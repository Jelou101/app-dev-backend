<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UserStatusController extends Controller
{
    /**
     * Get user's current points
     */
    public function getPoints()
    {
        $authUser = Auth::user();
        
        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }
        
        // Get the full User model
        $user = User::find($authUser->user_id);
        
        // Return points with defaults
        return response()->json([
            'success' => true,
            'total_points' => $user->total_points ?? 1000,
            'riddle_points' => $user->riddle_points ?? 0,
            'logic_points' => $user->logic_points ?? 0,
            'endurance_points' => $user->endurance_points ?? 0
        ]);
    }
    
    /**
     * Add points to any category
     */
    public function addPoints(Request $request)
    {
        $request->validate([
            'points' => 'required|integer|min:1',
            'category' => 'required|in:total,riddle,logic,endurance'
        ]);
        
        $authUser = Auth::user();
        $user = User::find($authUser->user_id);
        $category = $request->category . '_points';
        
        // Initialize if null
        if ($user->$category === null) {
            $user->$category = ($category === 'total_points') ? 1000 : 0;
        }
        
        $user->$category += $request->points;
        
        // Also update total_points if adding to specific category
        if ($category !== 'total_points') {
            if ($user->total_points === null) {
                $user->total_points = 1000;
            }
            $user->total_points += $request->points;
        }
        
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Points added successfully',
            'new_points' => $user->$category,
            'total_points' => $user->total_points
        ]);
    }
    
    /**
     * Deduct points from any category
     */
    public function deductPoints(Request $request)
    {
        $request->validate([
            'points' => 'required|integer|min:1',
            'category' => 'required|in:total,riddle,logic,endurance'
        ]);
        
        $authUser = Auth::user();
        $user = User::find($authUser->user_id);
        $category = $request->category . '_points';
        
        // Initialize if null
        if ($user->$category === null) {
            $user->$category = ($category === 'total_points') ? 1000 : 0;
        }
        
        // Ensure we don't go below minimum
        $newPoints = max(0, $user->$category - $request->points);
        $user->$category = $newPoints;
        
        // Also update total if deducting from specific category
        if ($category !== 'total_points' && $user->total_points !== null) {
            $user->total_points = max(0, $user->total_points - $request->points);
        }
        
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Points deducted successfully',
            'new_points' => $user->$category
        ]);
    }
    
    /**
     * Add game points - MAIN METHOD FOR YOUR GAME
     */
    public function addGamePoints(Request $request)
    {
        $request->validate([
            'points' => 'required|integer',
            'game_mode' => 'required|in:riddle,logic,endurance'
        ]);
        
        $authUser = Auth::user();
        $user = User::find($authUser->user_id);
        $category = $request->game_mode . '_points';
        
        // Initialize if null
        if ($user->$category === null) {
            $user->$category = 0;
        }
        if ($user->total_points === null) {
            $user->total_points = 1000;
        }
        
        // Add points
        $user->$category += $request->points;
        $user->total_points += $request->points;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Game points added successfully',
            'game_points' => $user->$category,
            'total_points' => $user->total_points,
            'user_id' => $user->user_id
        ]);
    }
    
    /**
     * Reset points to default
     */
    public function resetPoints()
    {
        $authUser = Auth::user();
        $user = User::find($authUser->user_id);
        
        $user->total_points = 1000;
        $user->riddle_points = 0;
        $user->logic_points = 0;
        $user->endurance_points = 0;
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Points reset to 1000'
        ]);
    }
}