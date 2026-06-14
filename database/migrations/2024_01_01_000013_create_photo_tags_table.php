<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('photo_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_photo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->float('x_percent', 5, 2);
            $table->float('y_percent', 5, 2);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('photo_tags'); }
};
