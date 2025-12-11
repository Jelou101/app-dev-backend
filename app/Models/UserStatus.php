<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStatus extends Model
{
    use HasFactory;

    protected $primaryKey = 'stat_id';
    
    protected $fillable = [
        'user_id',
        'total_points',
        'riddle_points',      // ADD
        'logic_points',       // ADD  
        'endurance_points',   // ADD
        'total_puzzles_solved',
        'best_endurance_streak',
        'best_endurance_score',
        'riddles_solved',
        'logic_solved',
        'last_played'
    ];

    protected $attributes = [
        'total_points' => 1000,  // Default 1000 points
        'riddle_points' => 0,
        'logic_points' => 0,
        'endurance_points' => 0,
        'total_puzzles_solved' => 0,
        'best_endurance_streak' => 0,
        'best_endurance_score' => 0,
        'riddles_solved' => 0,
        'logic_solved' => 0,
    ];

    protected $casts = [
        'last_played' => 'datetime',
        'total_points' => 'integer',
        'riddle_points' => 'integer',
        'logic_points' => 'integer',
        'endurance_points' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    // Add points methods
    public function addPoints($points)
    {
        $this->total_points += $points;
        $this->save();
        return $this->total_points;
    }
    
    public function deductPoints($points)
    {
        $this->total_points = max(0, $this->total_points - $points);
        $this->save();
        return $this->total_points;
    }
    
    public function addGamePoints($gameType, $points)
    {
        switch($gameType) {
            case 'riddles':
                $this->riddle_points += $points;
                break;
            case 'logic':
                $this->logic_points += $points;
                break;
            case 'endurance':
                $this->endurance_points += $points;
                break;
        }
        
        $this->total_points += $points;
        $this->save();
        
        return [
            'total' => $this->total_points,
            'game' => $this->{$gameType . '_points'}
        ];
    }
}