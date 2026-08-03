<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lectures', function (Blueprint $table) {
            // Rotating token: previous_token stays valid for one rotation window.
            $table->string('previous_token', 32)->nullable()->after('token');
            $table->dateTime('token_rotated_at')->nullable()->after('previous_token');
            // HMAC secret for the rotating 6-digit TOTP code (stable, not rotated).
            $table->string('totp_secret', 64)->nullable()->after('attendance_enabled');
            // Optional GPS geofence for the lecture venue.
            $table->boolean('gps_required')->default(false)->after('totp_secret');
            $table->decimal('latitude', 10, 7)->nullable()->after('gps_required');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        // Backfill pre-existing lectures so rotation + TOTP work immediately.
        $lectures = DB::table('lectures')->get();
        foreach ($lectures as $lecture) {
            DB::table('lectures')->where('id', $lecture->id)->update([
                'token'           => $lecture->token ?: Str::random(20),
                'previous_token'  => null,
                'token_rotated_at' => $lecture->token_rotated_at ?? now(),
                'totp_secret'     => Str::random(32),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lectures', function (Blueprint $table) {
            $table->dropColumn([
                'previous_token',
                'token_rotated_at',
                'totp_secret',
                'gps_required',
                'latitude',
                'longitude',
            ]);
        });
    }
};
