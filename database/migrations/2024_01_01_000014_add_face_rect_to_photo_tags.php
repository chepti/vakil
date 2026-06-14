<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('photo_tags') && !Schema::hasColumn('photo_tags', 'w_percent')) {
            Schema::table('photo_tags', function (Blueprint $table) {
                $table->float('w_percent', 5, 2)->default(10)->after('y_percent');
                $table->float('h_percent', 5, 2)->default(10)->after('w_percent');
            });
        }
    }
    public function down(): void {
        if (Schema::hasColumn('photo_tags', 'w_percent')) {
            Schema::table('photo_tags', function (Blueprint $table) {
                $table->dropColumn(['w_percent', 'h_percent']);
            });
        }
    }
};
