<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            // שנת התמונה שהוגדרה כפרופיל — מאפשרת להשוות "האם התמונה החדשה חדשה יותר?"
            // כשתמונת פרופיל מתעדכנת אוטומטית מתיוג פנים (ולא מפעולה מפורשת של המשתמש).
            $table->smallInteger('profile_photo_year')->unsigned()->nullable()->after('profile_photo');
        });

        Schema::table('photos', function (Blueprint $table) {
            // שנת הצילום של המקור (אם הגיע מתמונה משפחתית עם שנה מתויגת) — נשמרת על כל
            // רשומת תמונה כדי לדעת מתי היא צולמה, גם אם היא לא הוגדרה כפרופיל הנוכחי.
            $table->smallInteger('taken_year')->unsigned()->nullable()->after('crop_h');
        });
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('profile_photo_year');
        });
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('taken_year');
        });
    }
};
