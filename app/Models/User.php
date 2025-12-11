<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens; 
    use HasFactory, Notifiable;

    protected $primaryKey = 'user_id';
    
    protected $fillable = [
        'display_name',
        'email',
        'password',
        'total_points',
        'riddle_points',
        'logic_points',
        'endurance_points'
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's points with defaults
     */
    public function getPointsAttribute()
    {
        return [
            'total_points' => $this->total_points ?? 1000,
            'riddle_points' => $this->riddle_points ?? 0,
            'logic_points' => $this->logic_points ?? 0,
            'endurance_points' => $this->endurance_points ?? 0
        ];
    }
    
    // Your existing relationships here...
    public function user_status(){
        return $this->hasMany(UserStatus::class, 'user_id');
    }
    
    public function game_session(){
        return $this->hasMany(GameSession::class, 'user_id');
    }
}