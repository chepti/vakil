<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ברכה על דמות: לידה / יום הולדת / יום נישואין (הזוג מיוצג ע"י הדמות הראשונה בקשר)
        Schema::create('occasion_blessings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('occasion', 20);
            $table->unsignedSmallInteger('year');
            $table->string('kind', 20);
            $table->timestamps();
            $table->unique(['person_id', 'user_id', 'occasion', 'year', 'kind'], 'occasion_bless_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('occasion_blessings');
    }
};
