<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lecture extends Model
{
    protected $fillable = [
        'course_id',
        'lecturer_id',
        'title',
        'content',
        'scheduled_date',
        'ended_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'datetime',
            'ended_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }
}
