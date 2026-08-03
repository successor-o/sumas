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
        // Enforce one attendance record per student per lecture at the DB level.
        // `lecture_id` is nullable for legacy records, and NULLs never conflict
        // in a unique index (SQLite & MySQL), so old rows are unaffected.
        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(['lecture_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique(['lecture_id', 'student_id']);
        });
    }
};
