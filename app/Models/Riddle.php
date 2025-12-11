<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Riddle extends Model
{
    use HasFactory;

    protected $table = 'riddles';

    protected $fillable = [
        'question',
        'answer',
        'hint',
        'explanation',
        'source',
    ];
}