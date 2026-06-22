<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // מייל חודשי (ראש חודש) — מופעל כברירת מחדל לכל הרשומים
            $table->boolean('notify_monthly_digest')->default(true)->after('status');
            // מייל מיידי כשנוספת דמות חדשה לעץ — opt-in
            $table->boolean('notify_new_person')->default(false)->after('notify_monthly_digest');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notify_monthly_digest', 'notify_new_person']);
        });
    }
};
