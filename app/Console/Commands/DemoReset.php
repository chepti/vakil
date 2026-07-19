<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * איפוס אתר ההדגמה: מחיקת כל הנתונים והקבצים שהועלו, וזריעה מחדש של נתוני הדמה.
 * רץ רק כש-APP_DEMO=true — הגנה מפני הרצה בטעות על האתר האמיתי.
 * מיועד ל-cron לילי בשרת הדמו.
 */
class DemoReset extends Command
{
    protected $signature = 'demo:reset';

    protected $description = 'Reset the demo site: wipe all data and uploads, reseed fake demo data (APP_DEMO only)';

    public function handle(): int
    {
        if (!config('app.demo')) {
            $this->error('APP_DEMO is not enabled — refusing to reset this site.');
            return self::FAILURE;
        }

        // מחיקת קבצים שהועלו על-ידי מתנסים (הזורע יוצר מחדש את קבצי הדמו)
        foreach (['avatars', 'photos', 'events', 'family-photos', 'recipes', 'documents'] as $dir) {
            Storage::disk('public')->deleteDirectory($dir);
        }

        $this->call('migrate:fresh', ['--force' => true]);
        $this->call('db:seed', ['--class' => \Database\Seeders\DemoSeeder::class, '--force' => true]);

        $this->info('Demo site reset complete.');
        return self::SUCCESS;
    }
}
