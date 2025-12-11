<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\UserAnswers;
class Puzzle extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'puzzle_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'puzzle_type',
        'question_text',
        'correct_answer',
        'hint_text',
        'explanation_text',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // Optional: Add enum casting if using enums
    ];

    /**
     * Get the user answers for the puzzle.
     */
    public function userAnswers(): HasMany
    {
        return $this->hasMany(UserAnswers::class, 'puzzle_id', 'puzzle_id');
    }

    public $timestamps = false;
}