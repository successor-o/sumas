<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SUMAS SmartAttend — Users Table
 * Stores both students and the admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('name');
            $table->string('username')->nullable()->unique(); // admin only
            $table->string('matric')->nullable()->unique();   // student only
            $table->string('email')->unique();
            $table->string('phone', 25)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();

            // Role & status
            $table->enum('role', ['student', 'admin'])->default('student');
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');

            // Academic info (students)
            $table->string('dept', 120)->nullable();
            $table->string('level', 20)->nullable();
            $table->string('gender', 30)->nullable();
            $table->date('dob')->nullable();
            $table->string('state_of_origin', 60)->nullable();
            $table->string('faculty', 120)->nullable();

            // AI verification
            $table->boolean('verified')->default(false);
            $table->integer('docs_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('role');
            $table->index('status');
            $table->index(['role', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
