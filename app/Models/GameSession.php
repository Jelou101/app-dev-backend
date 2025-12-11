<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameSession extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'session_id';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'game_mode',
        'puzzle_type',
        'total_score',
        'puzzles_attempted',
        'puzzles_correct',
        'current_streak',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_score' => 'integer',
        'puzzles_attempted' => 'integer',
        'puzzles_correct' => 'integer',
        'current_streak' => 'integer',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array
     */
    protected $attributes = [
        'total_score' => 0,
        'puzzles_attempted' => 0,
        'puzzles_correct' => 0,
        'current_streak' => 0,
        'status' => 'active',
    ];

    /**
     * Get the user that owns the game session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Scope a query to only include active sessions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include completed sessions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Calculate the accuracy percentage.
     */
    public function getAccuracyAttribute(): float
    {
        if ($this->puzzles_attempted === 0) {
            return 0.0;
        }

        return round(($this->puzzles_correct / $this->puzzles_attempted) * 100, 2);
    }

    /**
     * Check if the session is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the session is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the session is abandoned.
     */
    public function isAbandoned(): bool
    {
        return $this->status === 'abandoned';
    }
}