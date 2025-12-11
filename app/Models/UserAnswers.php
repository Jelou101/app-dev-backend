<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAnswers extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'answer_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'puzzle_id',
        'user_answer',
        'is_correct',
        'points_earned',
        'answered_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_correct' => 'boolean',
        'points_earned' => 'integer',
        'answered_at' => 'datetime',
    ];

    /**
     * Get the game session that owns the user answer.
     */
    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'session_id', 'session_id');
    }

    /**
     * Get the puzzle that owns the user answer.
     */
    public function puzzle(): BelongsTo
    {
        return $this->belongsTo(Puzzle::class, 'puzzle_id', 'puzzle_id');
    }


    /**
     * Scope a query by session.
     */
    public $timestamps = false;
}