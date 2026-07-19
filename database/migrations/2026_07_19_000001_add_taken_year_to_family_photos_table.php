<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_photos', function (Blueprint $table) {
            // שנת הצילום — מזינה את ציר הזמן: כל הפנים שתויגו בתמונה מקבלות את השנה הזו
            $table->smallInteger('taken_year')->unsigned()->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('family_photos', function (Blueprint $table) {
            $table->dropColumn('taken_year');
        });
    }
};
