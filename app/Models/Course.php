<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Course extends Model
{
    protected $fillable = [
        'code',
        'name',
        'department',
        'department_id',
        'description',
        'credit_units',
        'level',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function lecturers(): BelongsToMany
    {
        return $this->belongsToMany(Lecturer::class, 'course_lecturer');
    }

    public function students(): BelongsToMany
    {
        // Pivot uses student_id — see create_student_courses_table migration
        return $this->belongsToMany(User::class, 'student_courses', 'course_id', 'student_id');
    }
}
