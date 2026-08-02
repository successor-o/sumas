<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('doc_type', 30);          // school-id | admission | clearance | nat-id | pp-1 | pp-2 | pp-3
            $table->string('original_name', 255);
            $table->string('stored_name', 255);
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'doc_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
