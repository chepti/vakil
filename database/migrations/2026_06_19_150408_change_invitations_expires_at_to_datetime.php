<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dateTime('expires_at')->change();
            $table->dateTime('used_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->timestamp('expires_at')->change();
            $table->timestamp('used_at')->nullable()->change();
        });
    }
};
