<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            // אזור החיתוך (אחוזים יחסית לתמונת המקור) — כדי לאפשר עריכת חיתוך חוזרת
            $table->float('crop_x')->nullable()->after('original_path');
            $table->float('crop_y')->nullable()->after('crop_x');
            $table->float('crop_w')->nullable()->after('crop_y');
            $table->float('crop_h')->nullable()->after('crop_w');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn(['crop_x', 'crop_y', 'crop_w', 'crop_h']);
        });
    }
};
