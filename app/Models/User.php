<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Roles: 'student' | 'admin'
     * Status: 'Pending' | 'Approved' | 'Rejected'
     */
    protected $fillable = [
        'name',
        'matric',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'dept',
        'level',
        'gender',
        'dob',
        'state_of_origin',
        'faculty',
        'verified',
        'face_enrolled_at',
        'face_photo',
        'docs_count',
        'username', // admin only
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'verified' => 'boolean',
            'face_enrolled_at' => 'datetime',
            'docs_count' => 'integer',
        ];
    }

    /* ── Relations ── */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function courses(): BelongsToMany
    {
        // Pivot uses student_id (not the default user_id) — see create_student_courses_table migration
        return $this->belongsToMany(Course::class, 'student_courses', 'student_id', 'course_id');
    }

    /* ── Scopes ── */
    public function scopeStudents($query)
    {
        return $query->where('role', 'student');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'Approved');
    }

    /* ── Helpers ── */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }
}
