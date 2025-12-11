<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProgress extends Model
{
    use HasFactory;

    protected $table = 'user_progress';

    protected $fillable = [
        'user_id',
        'game_mode',
        'current_score',
        'hint_count',
        'current_riddle',
        'attempted_riddles',
        'last_played_at'
    ];

    protected $casts = [
        'current_riddle' => 'array',
        'attempted_riddles' => 'array',
        'last_played_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}