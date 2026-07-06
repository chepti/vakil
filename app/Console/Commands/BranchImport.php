<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Branch\BranchImportService;
use Illuminate\Console\Command;

class BranchImport extends Command
{
    protected $signature = 'branch:import
        {zip : נתיב לקובץ ה-ZIP שנוצר ב-branch:export}
        {--admin= : מייל של משתמש אדמין באתר זה, שאליו ישויכו הרשומות המיובאות}
        {--keep-main : לא לשנות את is_main_person (ברירת מחדל: שורש-הענף הופך למרכז העץ)}';

    protected $description = 'מייבא ענף מאתר-אח: upsert לפי origin_uuid — הרצה חוזרת מעדכנת במקום לשכפל';

    public function handle(BranchImportService $service): int
    {
        $zipPath = $this->argument('zip');
        if (! is_file($zipPath)) {
            $this->error("קובץ לא נמצא: {$zipPath}");

            return self::FAILURE;
        }

        $adminEmail = $this->option('admin');
        $admin = $adminEmail
            ? User::where('email', $adminEmail)->first()
            : User::where('role', 'admin')->orderBy('id')->first();

        if (! $admin) {
            $this->error($adminEmail
                ? "לא נמצא משתמש עם המייל {$adminEmail}"
                : 'לא נמצא משתמש אדמין באתר. יש ליצור אחד או להעביר --admin=<email>');

            return self::FAILURE;
        }

        if (! $admin->isAdmin()) {
            $this->error("המשתמש {$admin->email} אינו אדמין");

            return self::FAILURE;
        }

        $this->info("מייבא מ-{$zipPath} (רשומות ישויכו ל-{$admin->email})...");

        $result = $service->import(
            $zipPath,
            $admin,
            setRootAsMain: ! $this->option('keep-main'),
        );

        $m = $result['manifest'];
        $this->info("מקור: {$m['source_app_url']} | יוצא: {$m['exported_at']} | שורש: {$m['root_name']}");

        $this->table(
            ['טבלה', 'נוצרו', 'עודכנו'],
            collect($result['stats'])->map(fn ($s, $t) => [$t, $s['created'], $s['updated']])->values(),
        );

        foreach ($result['warnings'] as $warning) {
            $this->warn($warning);
        }

        $this->info('הייבוא הושלם.');

        return self::SUCCESS;
    }
}
