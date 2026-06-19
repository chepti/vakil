<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('relationships', function (Blueprint $table) {
            // מסמן בן/בת זוג לשעבר (גרושים) — תקף רק לקשר מסוג spouse
            $table->boolean('is_former')->default(false)->after('marriage_date_hebrew');
        });
    }

    public function down(): void
    {
        Schema::table('relationships', function (Blueprint $table) {
            $table->dropColumn('is_former');
        });
    }
};
