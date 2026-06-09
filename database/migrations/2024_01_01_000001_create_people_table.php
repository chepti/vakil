<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->date('birth_date_gregorian')->nullable();
            $table->string('birth_date_hebrew', 50)->nullable(); // e.g. "ה׳ בתשרי תשפ״ד"
            $table->date('death_date_gregorian')->nullable();
            $table->string('death_date_hebrew', 50)->nullable();
            $table->boolean('is_deceased')->default(false);
            $table->string('profile_photo')->nullable();
            $table->text('bio')->nullable();
            $table->string('current_occupation', 255)->nullable(); // "מה עושה כיום"
            $table->string('city')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
