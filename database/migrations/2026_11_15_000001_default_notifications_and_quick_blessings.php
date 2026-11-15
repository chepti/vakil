<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ברירת מחדל למשתמשים חדשים: כל שלוש ההתראות מופעלות
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_new_person')->default(true)->change();
            $table->boolean('notify_new_event')->default(true)->change();
        });

        // ברכה מהירה מתוך המייל: סוג (mazal_tov / shefa) — בלי הודעה חופשית
        Schema::table('blessings', function (Blueprint $table) {
            $table->string('kind', 20)->nullable()->after('message');
            $table->unique(['event_id', 'user_id', 'kind'], 'blessings_quick_unique');
        });
    }

    public function down(): void
    {
        Schema::table('blessings', function (Blueprint $table) {
            $table->dropUnique('blessings_quick_unique');
            $table->dropColumn('kind');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_new_person')->default(false)->change();
            $table->boolean('notify_new_event')->default(false)->change();
        });
    }
};
