<?php

namespace App\Console\Commands;

use App\Mail\MonthlyDigestMail;
use App\Models\User;
use App\Services\DigestBuilder;
use App\Support\HebrewDate;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendMonthlyDigest extends Command
{
    protected $signature = 'digest:monthly
        {--force : שליחה גם אם היום אינו ראש חודש}
        {--date= : תאריך לועזי לבדיקה (YYYY-MM-DD) במקום היום}
        {--dry : בנייה והצגה בלבד, ללא שליחת מיילים}';

    protected $description = 'שולח את המייל החודשי (ראש חודש) לכל הרשומים שבחרו לקבלו';

    public function handle(DigestBuilder $builder): int
    {
        $when = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        if (! $this->option('force') && ! HebrewDate::isRoshChodesh($when)) {
            $this->info('היום אינו ראש חודש (' . HebrewDate::format($when) . ') — לא נשלח דבר. להרצה כפויה: --force');

            return self::SUCCESS;
        }

        $data = $builder->build($when);
        $this->info("מכין מייל לחודש {$data['monthName']} {$data['yearGematria']}:");
        $this->line('  תינוקות: ' . count($data['newBabies'])
            . ' | אירועים: ' . count($data['events'])
            . ' | ימי הולדת עגולים: ' . count($data['roundBirthdays'])
            . ' | ימי נישואין עגולים: ' . count($data['roundAnniversaries']));

        if ($this->option('dry')) {
            $this->warn('--dry: לא נשלחו מיילים.');

            return self::SUCCESS;
        }

        $recipients = User::query()
            ->where('notify_monthly_digest', true)
            ->where('status', 'active')
            ->whereNotNull('email')
            ->get();

        $sent = 0;
        $failed = 0;
        foreach ($recipients as $user) {
            try {
                Mail::to($user->email)->send(new MonthlyDigestMail($data, $user->name));
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                $this->error("נכשל ל-{$user->email}: {$e->getMessage()}");
                report($e);
            }
        }

        $this->info("נשלחו {$sent} מיילים" . ($failed ? " ({$failed} נכשלו)" : '') . '.');

        return self::SUCCESS;
    }
}
