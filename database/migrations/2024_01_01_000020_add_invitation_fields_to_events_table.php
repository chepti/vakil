<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->time('event_time')->nullable()->after('event_date');
            $table->string('location')->nullable()->after('hebrew_date');
            $table->string('invitation_image')->nullable()->after('location');
            $table->string('photos_link')->nullable()->after('invitation_image');
            $table->text('audience')->nullable()->after('photos_link');
            $table->foreignId('audience_branch_person_id')->nullable()->after('audience')
                ->constrained('people')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('audience_branch_person_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('audience_branch_person_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['event_time', 'location', 'invitation_image', 'photos_link', 'audience']);
        });
    }
};
