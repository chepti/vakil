<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('relationships', function (Blueprint $table) {
            // מסמן קשר הורה-ילד ששויך ידנית במפורש (גובר על המיזוג האוטומטי של בני-זוג)
            $table->boolean('is_explicit')->default(false)->after('is_former');
        });
    }

    public function down(): void
    {
        Schema::table('relationships', function (Blueprint $table) {
            $table->dropColumn('is_explicit');
        });
    }
};
