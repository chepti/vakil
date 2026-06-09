<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person1_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('person2_id')->constrained('people')->cascadeOnDelete();
            $table->enum('type', ['spouse', 'parent_child']);
            // For parent_child: person1 = parent, person2 = child
            $table->timestamps();

            $table->unique(['person1_id', 'person2_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relationships');
    }
};
