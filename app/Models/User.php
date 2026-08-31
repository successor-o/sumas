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
     * live scan embedding. Both are expected to be 128-dim vectors.
     *
     * Both vectors are L2-normalized before comparison so that the dot product
     * equals the true cosine similarity regardless of whether the original
     * descriptors were unit vectors.
     *
     * Returns 0.0 when either side is unusable.
     */
    public function faceSimilarity(array $liveEmbedding): float
    {
        $enrolled = $this->face_embedding;

        if (! is_array($enrolled) || count($enrolled) !== count($liveEmbedding) || count($liveEmbedding) === 0) {
            return 0.0;
        }

        // L2-normalize both vectors so the dot product IS the cosine
        // similarity — face-api.js descriptors are not always perfect unit
        // vectors, especially with TinyFaceDetector.
        $normEnrolled = $this->l2Norm($enrolled);
        $normLive     = $this->l2Norm($liveEmbedding);

        if ($normEnrolled < 1e-8 || $normLive < 1e-8) {
            return 0.0;
        }

        $dot = 0.0;
        $count = count($enrolled);
        for ($i = 0; $i < $count; $i++) {
            $dot += ($enrolled[$i] / $normEnrolled) * ($liveEmbedding[$i] / $normLive);
        }

        // Clamp to [-1, 1] to avoid floating-point overshoot.
        return max(-1.0, min(1.0, $dot));
    }

    /**
     * L2 (Euclidean) norm of a vector.
     */
    private function l2Norm(array $v): float
    {
        $sum = 0.0;
        foreach ($v as $x) {
            $sum += (float) $x * (float) $x;
        }

        return sqrt($sum);
    }

    /**
     * Check whether a live scan embedding looks like a valid face descriptor.
     * face-api.js returns garbage descriptors when the detector fires on
     * non-face regions, when the image is too blurry, or when the model
     * files fail to load.
     *
     * A valid descriptor should:
     *  - Be a 128-element numeric array
     *  - Have an L2 norm reasonably close to 1.0 (0.5–2.0 after the model's
     *    own normalization)
     *  - Have enough variance (not all zeros or nearly identical values)
     */
    public static function isValidFaceEmbedding(array $embedding): bool
    {
        if (count($embedding) !== 128) {
            return false;
        }

        $norm = 0.0;
        $min  = PHP_FLOAT_MAX;
        $max  = PHP_FLOAT_MIN;
        $sum  = 0.0;

        foreach ($embedding as $v) {
            $f = (float) $v;
            $norm += $f * $f;
            $min = min($min, $f);
            $max = max($max, $f);
            $sum += $f;
        }

        $norm = sqrt($norm);

        // L2 norm must be in a reasonable range.
        if ($norm < 0.1 || $norm > 10.0) {
            return false;
        }

        // Standard deviation must be meaningful — a flat/garbage embedding
        // has almost no variance.
        $mean = $sum / 128.0;
        $varSum = 0.0;
        foreach ($embedding as $v) {
            $d = (float) $v - $mean;
            $varSum += $d * $d;
        }
        $stddev = sqrt($varSum / 128.0);
        if ($stddev < 0.01) {
            return false;
        }

        return true;
    }

    /**
     * Verify a live scan embedding against the enrolled one, using the
     * configured similarity threshold (config/attendance.php).
     */
    public function verifyFace(array $liveEmbedding, ?float $threshold = null): bool
    {
        $threshold = $threshold ?? (float) config('attendance.face_similarity_threshold', 0.70);

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
