<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'member'])->default('member')->after('email');
            $table->enum('status', ['pending', 'active'])->default('active')->after('role');
            $table->foreignId('person_id')->nullable()->after('status')
                ->constrained('people')->nullOnDelete();
            $table->foreignId('invited_by')->nullable()->after('person_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status', 'person_id', 'invited_by']);
        });
    }
};
