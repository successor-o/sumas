<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Lecture extends Model
{
    protected $fillable = [
        'course_id',
        'lecturer_id',
        'title',
        'content',
        'token',
        'previous_token',
        'token_rotated_at',
        'scheduled_date',
        'ended_at',
        'is_active',
        'attendance_enabled',
        'totp_secret',
        'gps_required',
        'latitude',
        'longitude',
        'attendance_score',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'datetime',
            'ended_at' => 'datetime',
            'token_rotated_at' => 'datetime',
            'is_active' => 'boolean',
            'attendance_enabled' => 'boolean',
            'gps_required' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            'attendance_score' => 'float',
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

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /* ── Rotating token ── */

    /**
     * Lazily rotate the QR token once the rotation interval has elapsed.
     * The previous token is kept (valid for one more window) so students who
     * scanned just before the rotation can still check in.
     */
    public function ensureFreshToken(): void
    {
        $interval = (int) config('attendance.rotation_seconds', 60);

        if (! $this->token_rotated_at || $this->token_rotated_at->diffInSeconds(now()) >= $interval) {
            $this->update([
                'previous_token' => $this->token,
                'token' => Str::random(20),
                'token_rotated_at' => now(),
            ]);
            $this->refresh();
        }
    }

    /**
     * True when the supplied token is the current one, or the previous one
     * (which is at most one rotation old — the grace window).
     */
    public function matchesToken(string $token): bool
    {
        return $this->token === $token || $this->previous_token === $token;
    }

    /* ── Rotating 6-digit TOTP code ── */

    public function totp(int $step = 0): string
    {
        $counter = floor(time() / 30) + $step;
        $hash = hash_hmac('sha1', pack('N', $counter), (string) $this->totp_secret, true);
        $offset = ord($hash[19]) & 0x0f;
        $code = ((ord($hash[$offset]) & 0x7f) << 24)
              | (ord($hash[$offset + 1]) << 16)
              | (ord($hash[$offset + 2]) << 8)
              | ord($hash[$offset + 3]);

        return str_pad((string) ($code % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Validate a 6-digit code with ±1 step tolerance (the standard TOTP
     * clock-skew window, which also covers codes typed just as they roll).
     */
    public function validateTotp(string $code): bool
    {
        if (strlen($code) !== 6 || ! ctype_digit($code)) {
            return false;
        }

        foreach ([-1, 0, 1] as $step) {
            if (hash_equals($this->totp($step), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * When the current TOTP step expires (ISO timestamp, for live countdowns).
     */
    public function totpExpiresAt(): \Illuminate\Support\Carbon
    {
        $step = (int) floor(time() / 30) * 30;

        return now()->setTimestamp($step + 30);
    }

    /* ── GPS geofence ── */

    /**
     * Distance in metres (haversine) from the lecture venue to a point.
     */
    public function distanceTo(float $lat, float $lng): float
    {
        if ($this->latitude === null || $this->longitude === null) {
            return 0.0;
        }

        $earth = 6371000.0;
        $dLat = deg2rad($lat - (float) $this->latitude);
        $dLng = deg2rad($lng - (float) $this->longitude);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad((float) $this->latitude)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
