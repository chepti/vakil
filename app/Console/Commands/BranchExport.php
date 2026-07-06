<?php

namespace App\Console\Commands;

use App\Models\Person;
use App\Services\Branch\BranchExportService;
use Illuminate\Console\Command;

class BranchExport extends Command
{
    protected $signature = 'branch:export
        {person : מזהה דמות-השורש של הענף (id או origin_uuid)}
        {--output= : תיקיית יעד לקובץ ה-ZIP (ברירת מחדל: storage/app/branch-exports)}
        {--light : חבילה קלה — בלי תמונות-מקור מלאות (רק הגרסאות המוצגות)}
        {--dry : הצגת היקף הענף בלבד, בלי ליצור קובץ}';

    protected $description = 'מייצא ענף שלם (דמות-שורש + צאצאים + בני זוג + כל התוכן הצמוד) לקובץ ZIP להעברה לאתר-אח';

    public function handle(BranchExportService $service): int
    {
        $key = $this->argument('person');
        $root = is_numeric($key)
            ? Person::find($key)
            : Person::where('origin_uuid', $key)->first();

        if (! $root) {
            $this->error("לא נמצאה דמות: {$key}");

            return self::FAILURE;
        }

        $memberIds = $service->collectBranchMemberIds($root);
        $this->info("ענף של {$root->full_name}: " . count($memberIds) . ' דמויות');

        if ($this->option('dry')) {
            $names = Person::whereIn('id', $memberIds)->orderBy('birth_date_gregorian')->get();
            foreach ($names as $p) {
                $this->line('  • ' . $p->full_name . ($p->id === $root->id ? ' (שורש)' : ''));
            }

            return self::SUCCESS;
        }

        $outputDir = $this->option('output') ?: storage_path('app/branch-exports');
        $zipPath = $service->export($root, $outputDir, skipOriginals: (bool) $this->option('light'));

        $this->info("נוצר: {$zipPath}");
        $this->line('גודל: ' . round(filesize($zipPath) / 1048576, 1) . ' MB');

        foreach ($service->warnings as $table => $notes) {
            foreach ($notes as $note) {
                $this->warn("[{$table}] {$note}");
            }
        }

        $this->line('');
        $this->line('לייבוא באתר היעד:');
        $this->line("  php artisan branch:import <path-to-zip> --admin=<email>");

        return self::SUCCESS;
    }
}
