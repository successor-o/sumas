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
        Schema::create('lectures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lecturer_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->dateTime('scheduled_date');
            $table->dateTime('ended_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['course_id', 'scheduled_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lectures');
    }
};
