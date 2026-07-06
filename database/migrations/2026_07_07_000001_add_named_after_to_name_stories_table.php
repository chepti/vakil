<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('name_stories', function (Blueprint $table) {
            // "על שם מי" — קישור לדמות בעץ שהילד נקרא על שמה (למשל סבא)
            $table->foreignId('named_after_person_id')
                ->nullable()
                ->after('created_by')
                ->constrained('people')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('name_stories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('named_after_person_id');
        });
    }
};
