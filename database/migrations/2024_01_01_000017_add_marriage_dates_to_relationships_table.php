<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('relationships', function (Blueprint $table) {
            $table->date('marriage_date_gregorian')->nullable()->after('type');
            $table->string('marriage_date_hebrew', 50)->nullable()->after('marriage_date_gregorian');
        });
    }

    public function down(): void
    {
        Schema::table('relationships', function (Blueprint $table) {
            $table->dropColumn(['marriage_date_gregorian', 'marriage_date_hebrew']);
        });
    }
};
