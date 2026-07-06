<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * הטבלאות שנוסעות ב"העברת ענף" — כל אחת מקבלת origin_uuid יציב
     * שמשמש כתעודת-זהות גלובלית חוצת-אתרים (ייצוא/ייבוא + סנכרון עתידי).
     */
    private array $tables = [
        'people',
        'relationships',
        'events',
        'name_stories',
        'family_photos',
        'photo_tags',
        'photos',
    ];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            if (! Schema::hasTable($name) || Schema::hasColumn($name, 'origin_uuid')) {
                continue;
            }

            Schema::table($name, function (Blueprint $table) {
                $table->uuid('origin_uuid')->nullable()->unique()->after('id');
            });

            // Backfill — מילוי UUID לכל רשומה קיימת.
            DB::table($name)->whereNull('origin_uuid')->orderBy('id')
                ->each(function ($row) use ($name) {
                    DB::table($name)->where('id', $row->id)
                        ->update(['origin_uuid' => (string) Str::uuid()]);
                });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            if (Schema::hasTable($name) && Schema::hasColumn($name, 'origin_uuid')) {
                Schema::table($name, function (Blueprint $table) {
                    $table->dropUnique([$table->getTable() . '_origin_uuid_unique']);
                    $table->dropColumn('origin_uuid');
                });
            }
        }
    }
};
