<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogicQuestion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'question',
        'options',
        'answer',
        'hint',
        'explanation',
        'source',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // 'options' => 'array', // REMOVE or COMMENT this line
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Accessor to format options as array when needed
     */
    public function getOptionsArrayAttribute()
    {
        // Parse the options string into array
        $options = trim($this->options);
        
        // Try to parse A) option1 B) option2 format
        if (preg_match_all('/([A-D])\)\s*([^A-D)]+)/', $options, $matches)) {
            $result = [];
            foreach ($matches[1] as $index => $letter) {
                $result[$letter] = trim($matches[2][$index]);
            }
            return $result;
        }
        
        // Fallback: return as is
        return $options;
    }
}