<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // מייל מיידי כשנוסף אירוע חדש — opt-in
            $table->boolean('notify_new_event')->default(false)->after('notify_new_person');
            // ענף לעדכון ברזולוציה גבוהה: כל ימי ההולדת/נישואין של הצאצאים מתחת לדמות זו
            $table->foreignId('digest_branch_person_id')->nullable()->after('notify_new_event')
                ->constrained('people')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['digest_branch_person_id']);
            $table->dropColumn(['notify_new_event', 'digest_branch_person_id']);
        });
    }
};
