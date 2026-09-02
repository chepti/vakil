<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // תוצאה לכל סבב משחק שהושלם, לכל משתמש — כדי לצבור ניקוד אישי ולהציג
        // "את מי כבר זיהית". GameStat (הקיים) נשאר צובר לפי דמות, לתגי העץ.
        Schema::create('game_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->unsignedSmallInteger('points')->default(0);
            $table->boolean('first_name_ok')->default(false);   // זוהה השם הפרטי
            $table->boolean('last_name_ok')->default(false);    // זוהה שם המשפחה
            $table->unsignedTinyInteger('links_total')->default(0);   // חוליות בשרשרת המשפחתית
            $table->unsignedTinyInteger('links_ok')->default(0);      // חוליות שנפתרו נכון
            $table->unsignedTinyInteger('hints_used')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_results');
    }
};
