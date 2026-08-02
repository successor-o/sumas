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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lecturer_id')->constrained()->cascadeOnDelete();
            $table->dateTime('lecture_date');
            $table->enum('status', ['present', 'absent', 'late'])->default('absent');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'course_id', 'lecture_date']);
            $table->index(['course_id', 'lecture_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
