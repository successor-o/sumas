<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lectures', function (Blueprint $table) {
            // Random token embedded in the QR code. Kept server-side only — the
            // student-facing lecture payload never exposes it, so the code can
            // only be obtained by scanning the lecturer's QR.
            $table->string('token', 32)->nullable()->unique()->after('content');
            // Optional per-lecture toggle for QR smart attendance.
            $table->boolean('attendance_enabled')->default(true)->after('is_active');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('lecture_id')->nullable()->after('student_id')->constrained('lectures')->cascadeOnDelete();
            // Device that performed a QR scan — locks a device to one scan per lecture.
            $table->string('device_id', 64)->nullable()->after('status');
            // Where the record came from: qr (scan) or manual (lecturer entry).
            $table->string('source', 16)->default('manual')->after('device_id');

            $table->index(['lecture_id', 'student_id']);
            $table->index(['lecture_id', 'device_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lecture_id');
            $table->dropColumn(['device_id', 'source']);
        });

        Schema::table('lectures', function (Blueprint $table) {
            $table->dropColumn(['token', 'attendance_enabled']);
        });
    }
};
