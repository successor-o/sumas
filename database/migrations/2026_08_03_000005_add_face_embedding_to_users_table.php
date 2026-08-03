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
        Schema::table('users', function (Blueprint $table) {
            // 128-dim FaceNet embedding computed in the browser (face-api.js)
            // at enrollment time. Stored here so attendance check-in can verify
            // a student's live face scan against this reference without any
            // server-side ML or third-party service.
            $table->json('face_embedding')->nullable()->after('face_photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('face_embedding');
        });
    }
};
