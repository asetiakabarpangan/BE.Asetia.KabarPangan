<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyStatistic extends Model
{
    protected $fillable = ['date', 'module', 'data'];

    protected $casts = [
        'date' => 'date',
        'data' => 'array',
    ];
}
