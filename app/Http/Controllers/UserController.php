<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function login(Request $request)
{
    // Validate input
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    // Find user
    $user = User::where('email', $request->email)->first();

    // Check credentials
    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            "message" => 'Incorrect email or password'
        ], 401);
    }

    // Create API token using Sanctum
    $token = $user->createToken('auth_token')->plainTextToken;

    // Get user points (with defaults)
    $points = [
        'total_points' => $user->total_points ?? 1000,
        'riddle_points' => $user->riddle_points ?? 0,
        'logic_points' => $user->logic_points ?? 0,
        'endurance_points' => $user->endurance_points ?? 0
    ];

    // Return response with token and user data
    return response()->json([
        "message" => 'Login Successfully!',
        "user" => [
            'user_id' => $user->user_id,
            'display_name' => $user->display_name,
            'email' => $user->email,
            'total_points' => $points['total_points'],
            'riddle_points' => $points['riddle_points'],
            'logic_points' => $points['logic_points'],
            'endurance_points' => $points['endurance_points']
        ],
        "token" => $token
    ], 200);
}

    /**
     * Show the form for creating a new resource.
     */

    public function register(Request $request)
    {
            $validated = $request->validate([
                "displayName" => 'required|string',
                'password' => 'required|string'
            ]);
            $validated['email'] = $request->input('email');

            $checkEmail = User::where('email', $validated['email'])->first();
            if($checkEmail){
            return response()->json(["message" => 'Email is already exist!'],422);
            }
            $validated['display_name'] = $validated['displayName'];
            unset($validated['displayName']);
            $validated['password'] = Hash::make($validated['password']);

            // Initialize points for new user
            $validated['total_points'] = 1000; // Starting points
            $validated['riddle_points'] = 0;
            $validated['logic_points'] = 0;
            $validated['endurance_points'] = 0;

            $user = User::create($validated);

            return response()->json([
                'message' => 'User created successfully!',
                'user' => $user
            ], 201);


    }


}
