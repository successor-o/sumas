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
        'face_embedding',
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
            'face_embedding' => 'array',
            'docs_count' => 'integer',
        ];
    }

    /* ── Face-scan attendance helpers ── */

    /**
     * True when the student has an enrolled FaceNet embedding that can be used
     * to verify a live face scan during attendance check-in.
     */
    public function hasFaceEnrolled(): bool
    {
        return is_array($this->face_embedding) && count($this->face_embedding) > 0;
    }

    /**
     * Cosine similarity between the student's enrolled FaceNet embedding and a
     * live scan embedding. Both are expected to be 128-dim unit vectors.
     * Returns 0.0 when either side is unusable.
     */
    public function faceSimilarity(array $liveEmbedding): float
    {
        $enrolled = $this->face_embedding;

        if (! is_array($enrolled) || count($enrolled) !== count($liveEmbedding) || count($liveEmbedding) === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $a   = 0.0;
        $b   = 0.0;
        foreach ($enrolled as $i => $value) {
            $dot += (float) $value * (float) $liveEmbedding[$i];
            $a   += (float) $value ** 2;
            $b   += (float) $liveEmbedding[$i] ** 2;
        }

        if ($a <= 0.0 || $b <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($a) * sqrt($b));
    }

    /**
     * Verify a live scan embedding against the enrolled one, using the
     * configured similarity threshold (config/attendance.php).
     */
    public function verifyFace(array $liveEmbedding, ?float $threshold = null): bool
    {
        $threshold = $threshold ?? (float) config('attendance.face_similarity_threshold', 0.55);

        return $this->faceSimilarity($liveEmbedding) >= $threshold;
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
