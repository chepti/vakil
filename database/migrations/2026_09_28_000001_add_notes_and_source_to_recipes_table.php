<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            // טיפים וסיפורים שמלווים את המתכון (למשל מתוך ספר המתכונים של סבתא)
            $table->text('notes')->nullable()->after('preparation');
            // מקור המתכון — ספר, מחברת וכו'
            $table->string('source')->nullable()->after('owner_text');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn(['notes', 'source']);
        });
    }
};
